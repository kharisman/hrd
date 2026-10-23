<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\RiwayatPendidikan;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Impor riwayat pendidikan dari .xlsx/.xls/.csv. Baris pertama = judul kolom (lihat KOLOM).
// Orang dicocokkan lewat nip (pegawai), nidn (dosen), atau username eUniversity.
class ImporRiwayat
{
    const KOLOM = ['nip', 'nidn', 'username', 'jenjang', 'institusi', 'program_studi', 'tahun_masuk', 'tahun_lulus', 'status', 'pembiayaan', 'keterangan'];

    /**
     * @param bool $simpan false = hanya cek (tidak ada yang disimpan)
     * @return array{baris: array<int, array{no:int, nama:?string, hasil:string, pesan:string}>, ringkas: array}
     */
    public function jalankan(string $path, bool $simpan): array
    {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $judul = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($rows) ?? []);
        if (!array_intersect(['nip', 'nidn', 'username'], $judul) || !in_array('jenjang', $judul) || !in_array('institusi', $judul)) {
            throw new \InvalidArgumentException('Judul kolom tidak sesuai template. Wajib ada: jenjang, institusi, dan salah satu dari nip / nidn / username.');
        }

        $hasil = [];
        $terdampak = [];
        DB::beginTransaction();
        foreach ($rows as $i => $row) {
            $no = $i + 2; // nomor baris di Excel (baris 1 = judul)
            $d = array_map(fn ($v) => is_string($v) ? trim($v) : $v, array_combine($judul, array_slice(array_pad($row, count($judul), null), 0, count($judul))));
            if (!array_filter($d, fn ($v) => $v !== null && $v !== '')) {
                continue; // baris kosong
            }

            [$staff, $dosen, $nama] = $this->cariOrang($d);
            if (!$staff && !$dosen) {
                $hasil[] = ['no' => $no, 'nama' => null, 'hasil' => 'gagal', 'pesan' => 'Orang tidak ditemukan (cek nip / nidn / username).'];
                continue;
            }

            $data = [
                'staff_id' => $staff?->id ?? $dosen?->staff_id,
                'dosen_id' => $dosen?->id ?? $staff?->dosen?->id,
                'jenjang' => $this->cocok($d['jenjang'] ?? null, RiwayatPendidikan::JENJANG),
                'institusi' => $d['institusi'] ?? null,
                'program_studi' => $d['program_studi'] ?? null ?: null,
                'tahun_masuk' => $d['tahun_masuk'] ?? null ?: null,
                'tahun_lulus' => $d['tahun_lulus'] ?? null ?: null,
                'status' => $this->cocok($d['status'] ?? null, RiwayatPendidikan::STATUS) ?? 'Lulus',
                'pembiayaan' => $this->cocok($d['pembiayaan'] ?? null, RiwayatPendidikan::PEMBIAYAAN),
                'keterangan' => $d['keterangan'] ?? null ?: null,
            ];

            $v = Validator::make($data, [
                'jenjang' => ['required', Rule::in(RiwayatPendidikan::JENJANG)],
                'institusi' => 'required|string|max:200',
                'tahun_masuk' => 'nullable|integer|min:1950|max:2100',
                'tahun_lulus' => 'nullable|integer|min:1950|max:2100' . ($data['tahun_masuk'] ? '|gte:tahun_masuk' : ''),
            ], [
                'jenjang.required' => 'Jenjang kosong/tidak dikenal (pilihan: ' . implode(', ', RiwayatPendidikan::JENJANG) . ').',
                'tahun_lulus.gte' => 'Tahun lulus sebelum tahun masuk.',
            ]);
            if ($v->fails()) {
                $hasil[] = ['no' => $no, 'nama' => $nama, 'hasil' => 'gagal', 'pesan' => implode(' ', $v->errors()->all())];
                continue;
            }

            $sudahAda = RiwayatPendidikan::milik($staff ?? $dosen?->staff, $dosen ?? $staff?->dosen)
                ->where('jenjang', $data['jenjang'])->where('institusi', $data['institusi'])->exists();
            if ($sudahAda) {
                $hasil[] = ['no' => $no, 'nama' => $nama, 'hasil' => 'lewati', 'pesan' => "{$data['jenjang']} {$data['institusi']} sudah ada."];
                continue;
            }

            RiwayatPendidikan::create($data);
            $terdampak[$data['staff_id'] . '-' . $data['dosen_id']] = [$data['staff_id'], $data['dosen_id']];
            $hasil[] = ['no' => $no, 'nama' => $nama, 'hasil' => 'ok', 'pesan' => "{$data['jenjang']} {$data['institusi']} ({$data['status']})"];
        }

        if ($simpan) {
            foreach ($terdampak as [$staffId, $dosenId]) {
                RiwayatPendidikan::perbaruiPendidikanTerakhir($staffId, $dosenId);
            }
            DB::commit();
        } else {
            DB::rollBack(); // mode cek: semua dibatalkan, tapi duplikat di dalam file tetap terdeteksi
        }

        return ['baris' => $hasil, 'ringkas' => collect($hasil)->countBy('hasil')->all() + ['ok' => 0, 'gagal' => 0, 'lewati' => 0]];
    }

    private function cariOrang(array $d): array
    {
        $nip = $d['nip'] ?? null;
        $nidn = $d['nidn'] ?? null;
        $username = $d['username'] ?? null;

        // Satu NIP bisa beberapa akun staff (beda divisi): ambil akun pertama
        $staff = $nip ? Staff::where('nip', $nip)->orderBy('id')->first() : null;
        $dosen = $nidn ? Dosen::where('nidn', $nidn)->first() : null;
        if (!$staff && !$dosen && $username) {
            $staff = Staff::where('username', $username)->first();
            $dosen = Dosen::where('username', $username)->first();
        }
        return [$staff, $dosen, $staff->nama ?? $dosen->nama ?? null];
    }

    // Cocokkan tanpa peduli huruf besar/kecil: "s2" → "S2", "sedang studi lanjut" → "Sedang Studi Lanjut"
    private function cocok($nilai, array $pilihan): ?string
    {
        foreach ($pilihan as $p) {
            if (strcasecmp(trim((string) $nilai), $p) === 0) {
                return $p;
            }
        }
        return null;
    }
}
