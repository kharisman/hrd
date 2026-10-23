<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Dosen;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

// Tarik master staff (= pegawai) & dosen dari API eUniversity ke database HRD.
// Aturan: data identitas/biodata dari API boleh menimpa; isian khusus HRD tidak disentuh
// (staff: atasan, status kepegawaian, pendidikan; dosen: jabfung, prodi, status, serdos).
// Status aktif dosen mengikuti StatusLogin eUniversity bila tersedia.
class Sinkron
{
    public function __construct(private PctApi $api)
    {
    }

    public function jalankan(): array
    {
        $data = $this->api->ambilSemua(['/staff', '/dosen']);
        $staff = collect($data['/staff']);
        $dosen = collect($data['/dosen']);

        // /staff tidak punya status aktif → pakai StatusLogin akun login staff (/users). Bila endpoint ini
        // tidak bisa dibaca (izin), status pegawai tidak diubah.
        $statusLogin = rescue(fn () => collect($this->api->ambilSemua(['/users'], ['tipe_user' => 'staff'])['/users'])
            ->mapWithKeys(fn ($u) => [strtolower($u['Username']) => $u['StatusLogin']]), null, false);

        $stat = [
            'staff_api' => $staff->count(), 'staff_aktif' => 0, 'staff_dihapus' => 0,
            'dosen_api' => $dosen->count(), 'dosen_baru' => 0, 'dosen_diperbarui' => 0, 'dosen_tertaut_pegawai' => 0, 'nidn_bentrok' => 0,
        ];

        DB::transaction(function () use ($staff, $dosen, $statusLogin, &$stat) {
            // 1. Master pegawai (hrd_staffs). Isian HRD (atasan, status, pendidikan) tidak ikut di-fill.
            foreach ($staff as $s) {
                Staff::updateOrCreate(['api_id' => $s['idStaff']], $this->biodata($s) + ($statusLogin ? [
                    // tanpa akun login sama sekali = tidak aktif
                    'aktif' => ($statusLogin[strtolower($s['Username'] ?? '')] ?? null) === 'aktif',
                ] : []) + [
                    'nip' => $this->bersih($s['Nip'] ?? null),
                    'nama' => trim($s['Nama']),
                    'username' => $s['Username'] ?: null,
                    'id_divisi' => $s['idDivisi'] ?: null,
                    'divisi' => $s['divisi'],
                    'id_level' => $s['idLevel'] ?: null,
                    'level' => $s['level'],
                    'mulai_kerja' => $this->tgl($s['mulaiKerja'] ?? null),
                    'disinkron_at' => now(),
                ]);
            }
            // Pegawai yang sudah dihapus di eUniversity ikut dihapus di HRD (tautan atasan/dosen/akun dilepas)
            $hilang = Staff::whereNotNull('api_id')->whereNotIn('api_id', $staff->pluck('idStaff'))->pluck('id');
            if ($hilang->isNotEmpty()) {
                Staff::whereIn('atasan_id', $hilang)->update(['atasan_id' => null]);
                Dosen::whereIn('staff_id', $hilang)->update(['staff_id' => null]);
                Akun::whereIn('staff_id', $hilang)->update(['staff_id' => null]);
                $stat['staff_dihapus'] = Staff::whereIn('id', $hilang)->delete();
            }

            // Akun dosen & staff di eUniversity beda username untuk orang yang sama, jadi dosen ditautkan lewat nama.
            // Satu orang bisa punya beberapa akun staff (NIP sama, beda divisi): nama dianggap unik bila NIP-nya satu.
            // ponytail: nama tanpa gelar; nama kembar (beda orang) dibiarkan untuk ditautkan manual
            $staffIdByNama = Staff::orderBy('id')->get(['id', 'nama', 'nip'])
                ->groupBy(fn ($p) => self::namaInti($p->nama))
                ->filter(fn ($g) => $g->pluck('nip')->unique()->count() === 1)
                ->map(fn ($g) => $g->first()->id);

            // 2. Master dosen, kunci idDosen (fallback: NIDN untuk dosen yang dulu diinput manual)
            foreach ($dosen as $d) {
                $nidn = $this->bersih($d['NIDN'] ?? null);
                $row = Dosen::where('api_id', $d['idDosen'])->first()
                    ?? ($nidn ? Dosen::whereNull('api_id')->where('nidn', $nidn)->first() : null)
                    ?? new Dosen(['aktif' => true]);
                $baru = !$row->exists;

                if ($nidn && Dosen::where('nidn', $nidn)->where('id', '!=', $row->id ?? 0)->exists()) {
                    $nidn = null; // NIDN dobel di API: biarkan kosong, perbaiki manual
                    $stat['nidn_bentrok']++;
                }

                $bio = $this->biodata($d);
                $row->fill([
                    'api_id' => $d['idDosen'],
                    'nama' => trim($d['Nama']),
                    'username' => $d['Username'] ?: null,
                    'jenis_kelamin' => $bio['jenis_kelamin'],
                    'tempat_lahir' => $bio['tempat_lahir'],
                    'tgl_lahir' => $bio['tgl_lahir'],
                    'agama' => $bio['agama'],
                    'gol_dar' => $bio['gol_dar'],
                    'alamat' => $bio['alamat'],
                    'mulai_kerja' => $this->tgl($d['mulaiKerja'] ?? null),
                    'disinkron_at' => now(),
                ]);
                // Status aktif ikut eUniversity (StatusLogin: aktif / tidak). Kosong = tanpa akun login,
                // atau API lama tanpa kolom ini: status HRD dibiarkan.
                $status = $d['StatusLogin'] ?? null;
                if (in_array($status, ['aktif', 'tidak'], true)) {
                    $row->aktif = $status === 'aktif';
                }
                $row->nidn = $nidn ?? $row->nidn;
                $row->no_hp = $row->no_hp ?: $bio['no_hp'];

                // Dosen yang juga pegawai: penghubungnya disimpan di HRD (staff_id), API tidak punya kolom ini.
                // Otomatis lewat nama; tautan manual dari form dosen tidak ditimpa.
                $staffId = $staffIdByNama[self::namaInti($d['Nama'])] ?? null;
                if (!$row->staff_id && $staffId && !Dosen::where('staff_id', $staffId)->exists()) {
                    $row->staff_id = $staffId;
                }

                if ($baru || collect($row->getDirty())->except('disinkron_at')->isNotEmpty()) {
                    $stat[$baru ? 'dosen_baru' : 'dosen_diperbarui']++;
                }
                $row->save();
            }
            $stat['dosen_tertaut_pegawai'] = Dosen::whereNotNull('staff_id')->count();
            $stat['staff_aktif'] = Staff::aktif()->count();

            // 3. Akun yang pernah login: perbarui tautan ke pegawai & dosen (username sama)
            foreach (Akun::all() as $akun) {
                $akun->update([
                    'staff_id' => Staff::where('username', $akun->username)->value('id'),
                    'dosen_id' => Dosen::where('username', $akun->username)->value('id'),
                ]);
            }
        });

        return $stat;
    }

    private function biodata(array $r): array
    {
        return [
            'jenis_kelamin' => ['Pria' => 'L', 'Wanita' => 'P'][$r['JenisKelamin'] ?? ''] ?? null,
            'tempat_lahir' => $this->bersih($r['TempatLahir'] ?? null),
            'tgl_lahir' => $this->tgl($r['TglLahir'] ?? null),
            'no_hp' => $this->bersih($r['Telp'] ?? null),
            'alamat' => $this->bersih($r['Alamat'] ?? null),
            'agama' => $this->bersih($r['Agama'] ?? null),
            'gol_dar' => in_array($r['golDar'] ?? null, ['A', 'B', 'AB', 'O']) ? $r['golDar'] : null,
        ];
    }

    // Data legacy memakai '-' / '' untuk kosong
    private function bersih(?string $v): ?string
    {
        $v = trim((string) $v);
        return $v === '' || $v === '-' ? null : $v;
    }

    // "Dr. Adelin, S.T., M.Kom." -> "adelin"
    public static function namaInti(string $nama): string
    {
        $n = strtolower(trim(explode(',', $nama)[0]));
        return trim(preg_replace('/^(dr|prof|ir|drs|dra|h|hj)\.?\s+/', '', preg_replace('/\s+/', ' ', $n)));
    }

    private function tgl(?string $v): ?string
    {
        return $v && preg_match('/^(\d{4})-\d\d-\d\d/', $v, $m) && $m[1] > 1900 ? substr($v, 0, 10) : null;
    }
}
