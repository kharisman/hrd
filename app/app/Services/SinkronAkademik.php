<?php

namespace App\Services;

use App\Models\Angkatan;
use App\Models\Bimbingan;
use App\Models\Dosen;
use App\Models\Kurikulum;
use App\Models\Mengajar;
use App\Models\MkInti;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\RasioProdi;
use App\Models\Struktur;

// Data akademik dari API eUniversity untuk dashboard dosen (dipanggil hrd:sync setelah Sinkron pegawai/dosen):
// prodi, jumlah mahasiswa aktif (rasio), riwayat mengajar, bimbingan PA, dan tautan MK inti ke kurikulum API.
class SinkronAkademik
{
    const PERIODE_MENGAJAR = 6; // periode reguler terakhir (≈ 3 tahun) untuk "pernah mengampu"

    /**
     * Langkah sinkron yang bisa dijalankan terpisah (menu Sinkron), urut sesuai ketergantungan.
     * [label, endpoint API, langkah yang perlu lebih dulu]
     */
    const LANGKAH = [
        'prodi' => ['Program Studi', '/prodi', []],
        'kurikulum' => ['Kurikulum', '/kurikulum', ['prodi']],
        'periode' => ['Periode Kuliah (KRS)', '/krs', []],
        'angkatan' => ['Angkatan', '/angkatan', ['prodi', 'kurikulum']],
        'struktur' => ['Struktur Divisi × Level', '/struktur', []],
        'mahasiswa' => ['Jumlah Mahasiswa Aktif (rasio)', '/mahasiswa', ['prodi', 'periode']],
        'dosen_pa' => ['Bimbingan Akademik (PA)', '/dosen-pa', ['pegawai_dosen']],
        'penguji' => ['Dosen Penguji', '/pkl-skripsi/penguji', ['pegawai_dosen']],
        'mengajar' => ['Kelas yang Diampu', '/krs/{id}/grup', ['pegawai_dosen', 'periode']],
        'bimbingan' => ['Bimbingan PKL / Proposal / Skripsi', '/pkl-skripsi/{jenis}/{idKRS}', ['pegawai_dosen', 'periode']],
        'tautan_mk' => ['Tautan MK Inti ke Kurikulum', '/kurikulum/{id}/detail', ['prodi', 'kurikulum', 'angkatan']],
    ];

    public function __construct(private PctApi $api) {}

    // Semua langkah berurutan (hrd:sync)
    public function jalankan(): array
    {
        return collect(array_keys(self::LANGKAH))->mapWithKeys(fn ($k) => [$k => $this->satu($k)])->all();
    }

    // Satu langkah; hasil = jumlah baris yang diproses. Waktu & hasil dicatat untuk halaman Sinkron.
    public function satu(string $langkah): int
    {
        $prodiS1 = fn () => Prodi::where(['aktif' => true, 'jenjang' => 'Strata Satu'])->get();
        $hasil = match ($langkah) {
            'prodi' => $this->prodi()->count(),
            'kurikulum' => $this->kurikulum(),
            'periode' => $this->periode(),
            'angkatan' => $this->angkatan(),
            'struktur' => $this->struktur(),
            'mahasiswa' => $this->rasio($prodiS1()),
            'dosen_pa' => $this->dosenPa(),
            'penguji' => $this->penguji(),
            'mengajar' => $this->mengajar(),
            'bimbingan' => $this->bimbingan(),
            'tautan_mk' => $this->tautkanMk($prodiS1()),
        };
        self::catat($langkah, $hasil);
        return $hasil;
    }

    public static function catat(string $langkah, int $hasil): void
    {
        \App\Models\Pengaturan::simpan(["sinkron:$langkah" => json_encode(['waktu' => now()->toDateTimeString(), 'hasil' => $hasil])]);
    }

    public static function terakhir(string $langkah): ?array
    {
        return json_decode((string) \App\Models\Pengaturan::nilai("sinkron:$langkah"), true) ?: null;
    }

    private function prodi()
    {
        foreach ($this->api->ambilSemua(['/prodi'])['/prodi'] as $p) {
            Prodi::updateOrCreate(['api_id' => $p['idPStudi']], [
                'kode' => $p['kodePStudi'], 'nama' => trim($p['programStudi']), 'jenjang' => $p['jenjang'], 'aktif' => $p['Aktif'] === 'yes',
            ]);
        }
        return Prodi::where(['aktif' => true, 'jenjang' => 'Strata Satu'])->get();
    }

    // Master kurikulum (/kurikulum): dirujuk angkatan & MK inti
    private function kurikulum(): int
    {
        foreach ($this->api->ambilSemua(['/kurikulum'])['/kurikulum'] as $k) {
            Kurikulum::updateOrCreate(['api_id' => $k['idKurikulum']], [
                'prodi_api_id' => $k['idPStudi'], 'kode' => $k['kdKurikulum'], 'nama' => trim(preg_replace('/\s+/', ' ', $k['nmKurikulum'])),
                'masa_studi' => $k['masaStudi'] ?: null, 'aktif' => $k['Aktif'] === 'yes',
            ]);
        }
        return Kurikulum::count();
    }

    // Periode kuliah (/krs) disimpan, dipakai rasio (tahun ajaran) & riwayat mengajar
    private function periode(): int
    {
        foreach ($this->api->ambilSemua(['/krs'])['/krs'] as $k) {
            PeriodeKrs::updateOrCreate(['api_id' => $k['idKRS']], [
                'tipe' => $k['tipe'], 'tahun_akademik' => $k['tahunAkademik'], 'mulai' => $k['tglMulaiBelajar'] ?: null, 'selesai' => $k['tglAkhirBelajar'] ?: null,
            ]);
        }
        return PeriodeKrs::count();
    }

    // Angkatan per prodi + kurikulum yang dipakainya
    private function angkatan(): int
    {
        foreach ($this->api->ambilSemua(['/angkatan'])['/angkatan'] as $a) {
            Angkatan::updateOrCreate(['api_id' => $a['idAngkatan']], [
                'prodi_api_id' => $a['idPStudi'], 'kode' => $a['Angkatan'], 'periode' => $a['periodeAngkatan'], 'semester' => $a['semester'],
                'id_kurikulum_api' => $a['idKurikulum'] ?: null, 'kurikulum' => trim(preg_replace('/\s+/', ' ', (string) $a['nmKurikulum'])) ?: null,
                'aktif' => $a['Aktif'] === 'yes',
            ]);
        }
        return Angkatan::count();
    }

    // Jumlah mahasiswa aktif per prodi S1 & per angkatan untuk tahun ajaran periode KRS terbaru (selalu dari API, termasuk 0)
    private function rasio($prodi): int
    {
        $ta = PeriodeKrs::tahunAjaran()->first();
        // Hanya kolom yang perlu (tanpa data pribadi): prodi & angkatan tiap mahasiswa aktif
        $mhs = collect($this->api->ambilSemua(['/mahasiswa'], ['status' => 'aktif', 'fields' => 'idMhs,idPStudi,Angkatan'])['/mahasiswa']);
        if (!$ta) {
            return 0;
        }
        foreach ($prodi as $p) {
            $milik = $mhs->where('idPStudi', $p->api_id);
            RasioProdi::updateOrCreate(['program_studi' => $p->nama, 'tahun_ajaran' => $ta], [
                'jumlah_mahasiswa' => $milik->count(), 'per_angkatan' => $milik->countBy('Angkatan')->sortKeysDesc()->all(),
            ]);
        }
        return $prodi->count();
    }

    private function dosenPa(): int
    {
        $n = 0;
        foreach ($this->api->ambilSemua(['/dosen-pa'])['/dosen-pa'] as $d) {
            $n += Dosen::where('api_id', $d['idDosen'])->update(['jumlah_pa' => (int) $d['jumlah']]);
        }
        return $n;
    }

    // Kelas reguler (aktif atau ada pendaftar) di beberapa periode ganjil/genap terakhir
    private function mengajar(): int
    {
        $periode = PeriodeKrs::whereIn('tipe', ['ganjil', 'genap'])->orderByDesc('api_id')->take(self::PERIODE_MENGAJAR)->get();
        $dosenAda = Dosen::whereNotNull('api_id')->pluck('id', 'api_id'); // FK mengajar → dosens.api_id
        $n = 0;
        foreach ($periode as $k) {
            $grup = collect($this->api->ambilSemua(["/krs/{$k->api_id}/grup"])["/krs/{$k->api_id}/grup"])
                ->filter(fn ($g) => $g['idDosen'] && ($g['Aktif'] === 'yes' || $g['jlhDaftar'] > 0) && isset($dosenAda[$g['idDosen']]));
            Mengajar::where('id_krs', $k->api_id)->whereNotIn('id_grup', $grup->pluck('idGrup'))->delete();
            foreach ($grup as $g) {
                Mengajar::updateOrCreate(['id_grup' => $g['idGrup']], [
                    'dosen_api_id' => $g['idDosen'], 'id_krs' => $k->api_id, 'periode' => ucfirst($k->tipe) . ' ' . $k->tahun_akademik,
                    'kode_mk' => $g['kodeMateri'], 'nama_mk' => trim($g['namaMateri']), 'sks' => $g['sks'],
                    'kelas' => trim($g['nmGrup'] . ' ' . $g['Sesi']), 'jumlah_mhs' => (int) $g['jlhDaftar'],
                ]);
                $n++;
            }
        }
        return $n;
    }

    // Pembimbing PKL / proposal / skripsi per periode reguler terakhir. Pembimbing dari API berupa nama → dicocokkan ke dosen.
    // ponytail: hanya judul & prodi yang disimpan, nama/NPM mahasiswa tidak dibutuhkan HRD
    private function bimbingan(): int
    {
        $dosen = Dosen::orderByDesc('aktif')->get(['id', 'nama'])->unique(fn ($d) => Sinkron::namaInti($d->nama))->keyBy(fn ($d) => Sinkron::namaInti($d->nama));
        $n = 0;
        // Hanya periode yang dilayani /pkl-skripsi (periode nonaktif/pendek dibalas 404)
        $valid = collect($this->api->detail('/pkl-skripsi'))->pluck('idKRS');
        foreach (PeriodeKrs::whereIn('api_id', $valid)->orderByDesc('api_id')->take(self::PERIODE_MENGAJAR)->get() as $k) {
            foreach (array_keys(Bimbingan::JENIS) as $jenis) {
                $item = collect($this->api->detail("/pkl-skripsi/$jenis/{$k->api_id}")['items'] ?? []);
                Bimbingan::where(['jenis' => $jenis, 'id_krs' => $k->api_id])->whereNotIn('api_id', $item->pluck('id'))->delete();
                foreach ($item->filter(fn ($b) => trim((string) $b['pembimbing'])) as $b) {
                    Bimbingan::updateOrCreate(['jenis' => $jenis, 'api_id' => $b['id']], [
                        'id_krs' => $k->api_id, 'periode' => ucfirst($k->tipe) . ' ' . $k->tahun_akademik,
                        'dosen_id' => $dosen[Sinkron::namaInti($b['pembimbing'])]->id ?? null, 'pembimbing' => trim($b['pembimbing']),
                        'program_studi' => $b['programStudi'] ?? null, 'judul' => mb_substr(trim((string) ($b['Judul'] ?? '')), 0, 500) ?: null,
                    ]);
                    $n++;
                }
            }
        }
        return $n;
    }

    private function penguji(): int
    {
        $ids = collect($this->api->detail('/pkl-skripsi/penguji'))->pluck('idDosen');
        Dosen::whereNotIn('api_id', $ids)->update(['penguji' => false]);
        return Dosen::whereIn('api_id', $ids)->update(['penguji' => true]);
    }

    // Master divisi × level (pilihan & validasi divisi/level pegawai)
    private function struktur(): int
    {
        foreach ($this->api->ambilSemua(['/struktur'])['/struktur'] as $s) {
            Struktur::updateOrCreate(['api_id' => $s['idStruktur']], [
                'id_divisi' => $s['idDivisi'], 'id_level' => $s['idLevel'], 'divisi' => trim($s['divisi']), 'level' => trim($s['level']), 'aktif' => $s['Aktif'] === 'yes',
            ]);
        }
        return Struktur::count();
    }

    /**
     * MK inti HRD → kurikulum API: label sama dengan kode kurikulum, atau label tahun ("2026") = kode berakhiran -2026,
     * label lain ("Lama") = kurikulum aktif terbaru sebelum itu. Nama MK dicocokkan tanpa beda huruf/spasi.
     */
    private function tautkanMk($prodi): int
    {
        $kurikulum = collect($this->api->ambilSemua(['/kurikulum'])['/kurikulum']);
        $n = 0;
        foreach (MkInti::select('program_studi', 'kurikulum')->distinct()->get() as $g) {
            $p = $prodi->firstWhere('nama', $g->program_studi);
            $calon = $kurikulum->where('idPStudi', $p?->api_id)->sortByDesc(fn ($k) => (int) substr($k['kdKurikulum'], -4));
            // Kurikulum berlaku = yang dipakai angkatan aktif prodi ini, terbaru dulu
            $berlaku = Angkatan::where(['prodi_api_id' => $p?->api_id, 'aktif' => true])->orderByDesc('periode')->pluck('id_kurikulum_api')->unique()->values();
            $dipakai = $berlaku->map(fn ($id) => $calon->firstWhere('idKurikulum', $id))->filter()->values();
            $k = $calon->firstWhere('kdKurikulum', $g->kurikulum) ?? (is_numeric($g->kurikulum)
                ? $calon->first(fn ($k) => str_ends_with($k['kdKurikulum'], $g->kurikulum))
                : $dipakai->get(1) ?? $dipakai->first()); // label lama ("Lama") = kurikulum berlaku kedua terbaru
            if (!$k) {
                continue;
            }
            $materi = collect($this->api->detail("/kurikulum/{$k['idKurikulum']}/detail")['semester'] ?? [])
                ->flatMap(fn ($s) => collect($s['materi'])->map(fn ($m) => $m + ['smt' => $s['semester']]));
            $perKode = $materi->keyBy('kodeMateri');
            $perNama = $materi->keyBy(fn ($m) => self::kunci($m['namaMateri']));

            // Data API diutamakan: label kurikulum = kode kurikulum eUniversity, nama/SKS/semester MK = versi API
            $grup = MkInti::where(['program_studi' => $g->program_studi, 'kurikulum' => $g->kurikulum])->get();
            foreach ($grup as $mk) {
                // Tautan manual (kode) didahulukan, lalu nama
                $m = ($mk->kode_mk ? $perKode[$mk->kode_mk] ?? null : null) ?? $perNama[self::kunci($mk->nama)] ?? $this->mirip($mk->nama, $perNama);
                $namaApi = $m ? trim($m['namaMateri']) : null;
                $mk->update(['kurikulum' => $k['kdKurikulum'], 'id_kurikulum_api' => $k['idKurikulum'], 'kode_mk' => $m['kodeMateri'] ?? null, 'sks' => $m['sks'] ?? null]
                    + ($m ? ['semester' => $m['smt'] % 2 ? 'Ganjil' : 'Genap'] : [])
                    + ($namaApi && !$grup->contains(fn ($x) => $x->id !== $mk->id && $x->nama === $namaApi) ? ['nama' => $namaApi] : []));
                $n += (bool) $m;
            }
        }
        return $n;
    }

    // "Pemrograman Berorientasi Obyek (OOP)" = "pemrograman berorientasi objek": tanpa kurung, huruf kecil, tanpa spasi/tanda
    public static function kunci(string $nama): string
    {
        return str_replace(['obyek', 'lanjutan'], ['objek', 'lanjut'], preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\(.*?\)/', '', $nama))));
    }

    // ponytail: kemiripan nama ≥ 85% dan hanya satu kandidat; nama yang jauh berbeda (UI/UX vs Desain Antarmuka) tetap ditautkan manual
    private function mirip(string $nama, $materi): ?array
    {
        $k = self::kunci($nama);
        $calon = $materi->filter(function ($m, $kunci) use ($k) {
            similar_text($k, $kunci, $persen);
            return $persen >= 85;
        });
        return $calon->count() === 1 ? $calon->first() : null;
    }
}
