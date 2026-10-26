<?php

namespace App\Services;

use App\Models\CapaianDosen;
use App\Models\Disc;
use App\Models\Dosen;
use App\Models\Kehadiran;
use App\Models\Kompetensi;
use App\Models\MkInti;
use App\Models\RasioProdi;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as TglExcel;

// Impor sekali jalan dari spreadsheet "Dashboard HRD" (tab db) & "Dashboard Dosen" (tab Jafung & Serdos per prodi, Rasio).
// Setelah itu data dirawat di HRD (form pegawai/dosen). Pegawai dicocokkan lewat NIP, dosen lewat staff tertaut atau nama.
class ImporDashboard
{
    private array $catatan = [];

    // Tab db: data kepegawaian per NIP → staffs (semua akun ber-NIP sama) + dosen tertautnya
    public function karyawan(string $file): array
    {
        $rows = $this->baris($this->tab($file, ['db'])->getSheetByName('db'));
        $judul = array_map(fn ($h) => trim((string) $h), array_shift($rows));
        $kol = fn ($nama) => array_search($nama, $judul, true);
        $hasil = ['pegawai' => 0, 'dosen' => 0, 'tak_cocok' => []];

        foreach ($rows as $r) {
            $nip = trim((string) ($r[$kol('NIP')] ?? ''));
            if (!$nip) {
                continue;
            }
            $ids = Staff::where('nip', $nip)->pluck('id');
            $nama = trim((string) $r[$kol('Nama Lengkap')]);
            if ($ids->isEmpty()) {
                $hasil['tak_cocok'][] = "$nip $nama";
                continue;
            }
            $status = $r[$kol('Status Karyawan')] ?? null;
            Staff::whereIn('id', $ids)->update(array_filter([
                'jabatan' => $r[$kol('Jabatan')] ?: null,
                'status_kepegawaian' => ['Karyawan Kontrak' => 'Kontrak', 'Karyawan Tetap' => 'Tetap'][$status] ?? null,
                'pendidikan_terakhir' => $r[$kol('Pendidikan Terakhir')] ?: null,
                'tanggal_keluar' => $r[$kol('Status')] === 'nonaktif' ? $this->tgl($r[$kol('Tanggal Terakhir Kerja')]) : null,
                'kontrak_berakhir' => $this->tgl($r[$kol('Tanggal Kontrak/SK Berakhir')]),
            ], fn ($v) => $v !== null));
            $hasil['pegawai']++;

            if (!str_starts_with((string) $status, 'Dosen')) {
                continue;
            }
            // Utamakan dosen aktif: orang yang sama bisa punya data dosen lama (nonaktif) yang masih tertaut
            $dosen = Dosen::whereIn('staff_id', $ids)->where('aktif', true)->first() ?? $this->dosenBernama($nama) ?? Dosen::whereIn('staff_id', $ids)->first();
            if (!$dosen) {
                $hasil['tak_cocok'][] = "$nip $nama (dosen)";
                continue;
            }
            $dosen->update(array_filter([
                'program_studi' => $this->prodi($r[$kol('Program Studi')] ?? null),
                'jabatan_fungsional' => $r[$kol('Jenjang Jabatan Akademik')] ?: null,
                'pendidikan_terakhir' => $r[$kol('Pendidikan Terakhir')] ?: null,
                'serdos' => ['Ya' => true, 'Tidak' => false][$r[$kol('Sertifikasi Dosen')]] ?? null,
                'status_dosen' => 'Tetap',
                'tmt_dosen' => $this->tgl($r[$kol('Tanggal SK Dosen')]),
            ], fn ($v) => $v !== null));
            $hasil['dosen']++;
        }
        return $hasil;
    }

    // Tab "Jafung & Serdos SI/IF/BD": TMT dosen, jafung + angka kredit (mis. "L 300"), TMT jafung
    public function jafung(string $file): array
    {
        $buku = $this->tab($file, $tabs = ['Jafung & Serdos SI', 'Jafung & Serdos IF', 'Jafung & Serdos BD']);
        $hasil = ['dosen' => 0, 'tak_cocok' => []];
        foreach ($tabs as $tab) {
            $rows = $this->baris($buku->getSheetByName($tab));
            $judul = null;
            foreach ($rows as $r) {
                $r = array_values(array_filter($r, fn ($v) => $v !== null && $v !== ''));
                if (in_array($r[0] ?? null, ['Nama', 'Nama Dosen']) && ($r[1] ?? null) === 'TMT') {
                    $judul = true; // baris judul tabel rincian
                    continue;
                }
                if (!$judul || !isset($r[3]) || !preg_match('/^(AA|L|LK|GB)\s*(\d+)?$/', trim((string) $r[2]), $m)) {
                    continue;
                }
                if (!$dosen = $this->dosenBernama($r[0])) {
                    $hasil['tak_cocok'][] = "{$r[0]} ($tab)";
                    continue;
                }
                $dosen->update(array_filter([
                    'program_studi' => $dosen->program_studi ?: ['SI' => 'Sistem Informasi', 'IF' => 'Informatika', 'BD' => 'Bisnis Digital'][substr($tab, -2)],
                    'tmt_dosen' => $this->tgl($r[1]),
                    'jabatan_fungsional' => ['AA' => 'Asisten Ahli', 'L' => 'Lektor', 'LK' => 'Lektor Kepala', 'GB' => 'Profesor'][$m[1]],
                    'angka_kredit' => isset($m[2]) ? (int) $m[2] : null,
                    'tmt_jabfung' => $this->tgl($r[3]),
                ], fn ($v) => $v !== null));
                $hasil['dosen']++;
            }
        }
        return $hasil;
    }

    // Tab "Data Keterlambatan" (menit) & "Data Kehadiran" (hari tidak hadir): NIP, tahun, kolom Januari–Desember
    public function kehadiran(string $file): int
    {
        $buku = $this->tab($file, $tabs = ['Data Keterlambatan' => 'terlambat_menit', 'Data Kehadiran' => 'tidak_hadir_hari']);
        $n = 0;
        foreach ($tabs as $tab => $kolom) {
            $rows = $this->baris($buku->getSheetByName($tab));
            $judul = collect($rows)->search(fn ($r) => in_array('Januari', $r, true));
            $awal = array_search('Januari', $rows[$judul], true);
            foreach (array_slice($rows, $judul + 2) as $r) {
                $nip = trim((string) ($r[2] ?? ''));
                if (!preg_match('/^\d+\.PCT\.\d+$/', $nip) || !is_numeric($r[5] ?? null)) {
                    continue;
                }
                foreach (range(0, 11) as $i) {
                    $v = $r[$awal + $i] ?? null;
                    if (is_numeric($v)) {
                        Kehadiran::updateOrCreate(['nip' => $nip, 'tahun' => (int) $r[5], 'bulan' => $i + 1], [$kolom => $v]);
                        $n++;
                    }
                }
            }
        }
        return $n;
    }

    // Tab "Penguasaan MK <prodi>" (kurikulum lama) & "... (Kur 2026)": MK inti × dosen (TRUE = menguasai)
    public function penguasaanMk(string $file): array
    {
        $tabs = [
            'Penguasaan MK SI' => ['Sistem Informasi', 'Lama'], 'Penguasaan MK IF' => ['Informatika', 'Lama'], 'Penguasaan MK BD' => ['Bisnis Digital', 'Lama'],
            'Penugasan MK SI (Kur 2026)' => ['Sistem Informasi', '2026'], 'Penguasaan MK IF (Kur 2026)' => ['Informatika', '2026'], 'Penguasaan MK BD (Kur 2026)' => ['Bisnis Digital', '2026'],
        ];
        $buku = $this->tab($file, $tabs);
        $hasil = ['mk' => 0, 'tak_cocok' => []];
        foreach ($tabs as $tab => [$prodi, $kurikulum]) {
            $rows = $this->baris($buku->getSheetByName($tab));
            $j = collect($rows)->search(fn ($r) => in_array('Mata Kuliah Inti', $r, true));
            $judul = array_map(fn ($v) => trim((string) $v), $rows[$j]);
            $kolMk = array_search('Mata Kuliah Inti', $judul);
            $kolIrisan = array_search('Irisan Prodi', $judul);
            $kolJumlah = array_search('Jumlah Dosen', $judul);
            $kolButuh = array_search('Kebutuhan dosen', $judul);

            // Kolom dosen = antara "Irisan Prodi" dan "Jumlah Dosen" (judulnya nama singkat)
            $dosen = [];
            foreach (range($kolIrisan + 1, $kolJumlah - 1) as $c) {
                ($d = $this->dosenSingkat($judul[$c], $prodi)) ? $dosen[$c] = $d->id : $hasil['tak_cocok'][] = "{$judul[$c]} ($tab)";
            }
            foreach (array_slice($rows, $j + 1) as $urut => $r) {
                $nama = trim((string) ($r[$kolMk] ?? ''));
                if (!$nama || !is_numeric($r[$kolJumlah] ?? null) && !collect($dosen)->keys()->contains(fn ($c) => is_bool($r[$c] ?? null))) {
                    continue;
                }
                $mk = MkInti::updateOrCreate(['program_studi' => $prodi, 'kurikulum' => $kurikulum, 'nama' => $nama], [
                    'bidang_ilmu' => $r[$kolMk + 1] ?: null, 'semester' => $r[$kolMk + 2] ?: null, 'irisan' => $r[$kolIrisan] ?: null,
                    'kebutuhan' => (int) ($r[$kolButuh] ?? 0) ?: 2, 'urutan' => $urut,
                    // Solusi/catatan: teks pertama sesudah kolom Ketercapaian
                    'solusi' => collect(array_slice($r, $kolButuh + 2))->first(fn ($v) => is_string($v) && trim($v) && !in_array(trim($v), ['Tercapai', 'Tidak Tercapai'])),
                ]);
                $mk->dosen()->sync(collect($dosen)->filter(fn ($id, $c) => ($r[$c] ?? false) === true)->values());
                $hasil['mk']++;
            }
        }
        return $hasil;
    }

    // Tab "Kompetensi <prodi> <tahun>": blok per dosen ("Nama Dosen : ..."), skill personal & jabatan
    public function kompetensi(string $file): array
    {
        $tabs = [];
        foreach (['SI', 'IF', 'BD'] as $p) {
            foreach ([2025, 2026] as $t) {
                $tabs["Kompetensi $p $t"] = $t;
            }
        }
        $buku = $this->tab($file, $tabs);
        $hasil = ['skill' => 0, 'tak_cocok' => []];
        foreach ($tabs as $tab => $tahun) {
            $dosen = null;
            $kelompok = null;
            foreach ($this->baris($buku->getSheetByName($tab)) as $r) {
                $label = trim((string) ($r[1] ?? ''));
                if (str_starts_with($label, 'Nama Dosen') && str_starts_with(trim((string) ($r[2] ?? '')), ':')) {
                    $nama = trim(ltrim(trim($r[2]), ':'));
                    $dosen = $this->dosenBernama($nama) ?? $this->dosenSingkat($nama, null);
                    $dosen ?: $hasil['tak_cocok'][] = "$nama ($tab)";
                    $kelompok = null;
                } elseif (in_array($label, ['Kompetensi Personal', 'Kompetensi Jabatan'])) {
                    $kelompok = $label === 'Kompetensi Personal' ? 'personal' : 'jabatan';
                } elseif ($dosen && $kelompok && $label && is_numeric($r[2] ?? null)) {
                    Kompetensi::updateOrCreate(['dosen_id' => $dosen->id, 'tahun' => $tahun, 'kelompok' => $kelompok, 'skill' => $label], [
                        'sekarang' => (int) $r[2], 'target' => is_numeric($r[3] ?? null) ? (int) $r[3] : null,
                        'rencana' => is_string($r[4] ?? null) ? mb_substr(trim($r[4]), 0, 255) : null,
                        'catatan' => is_string($r[8] ?? null) ? mb_substr(trim($r[8]), 0, 255) : null,
                    ]);
                    $hasil['skill']++;
                }
            }
        }
        return $hasil;
    }

    /**
     * Nama singkat di judul kolom spreadsheet → dosen: "Fatma", "Eka H", "Eka PS", "D Tri", "Hendra Hadi".
     * Kata pertama = awal nama depan; kata berikutnya = awal kata selanjutnya, atau huruf kapital = inisial berurutan.
     * Utamakan dosen prodi yang sama; hanya diterima bila kandidatnya tunggal.
     */
    private function dosenSingkat(string $singkat, ?string $prodi): ?Dosen
    {
        $kata = fn ($s) => array_values(array_filter(explode(' ', preg_replace('/[^a-z ]/', ' ', strtolower(explode(',', $s)[0])))));
        $cari = preg_split('/\s+/', trim($singkat));
        $cocok = function ($d) use ($cari, $kata) {
            $n = $kata($d->nama);
            $awal = strtolower(preg_replace('/[^A-Za-z]/', '', $cari[0]));
            // Kata pertama boleh di posisi mana pun ("Jhonsen" = Muhammad Jhonsen, "Hendra Hadi" = Dr. Hendra Hadiwijaya);
            // potongan berikutnya harus cocok berurutan dengan kata-kata sesudahnya (subsequence)
            foreach ($n as $i => $w) {
                if (!str_starts_with($w, $awal)) {
                    continue;
                }
                $sisa = array_slice($n, $i + 1);
                foreach (array_slice($cari, 1) as $c) {
                    foreach (ctype_upper($c) && strlen($c) <= 3 ? str_split(strtolower($c)) : [strtolower($c)] as $h) {
                        do {
                            if (!$sisa) {
                                continue 4;
                            }
                        } while (!str_starts_with(array_shift($sisa), $h));
                    }
                }
                return true;
            }
            return false;
        };
        $semua = Dosen::orderByDesc('aktif')->get()->filter($cocok);
        $sama = $prodi ? $semua->where('program_studi', $prodi) : collect();
        $pilih = $sama->count() === 1 ? $sama : $semua->where('aktif', true);
        return $pilih->count() === 1 ? $pilih->first() : ($semua->count() === 1 ? $semua->first() : null);
    }

    // Tab "DISC ..." (Dashboard HRD): peta karakter per orang; baris judul "Inisiatif ... Project"
    public function disc(string $file): array
    {
        $tabs = ['DISC Prodi SI' => 'Prodi SI', 'DISC Prodi IF' => 'Prodi IF', 'DISC Prodi BD' => 'Prodi BD', 'DISC Tim warek2' => 'Tim Warek II',
            'DISC SarPras' => 'SarPras', 'DISC Perpustakaan' => 'Perpustakaan', 'DISC Sales & Marketing' => 'Sales & Marketing'];
        $buku = $this->tab($file, $tabs);
        $hasil = ['orang' => 0, 'tak_cocok' => []];
        foreach ($tabs as $tab => $tim) {
            $rows = $this->baris($buku->getSheetByName($tab));
            $j = collect($rows)->search(fn ($r) => in_array('Inisiatif', array_map(fn ($v) => trim((string) $v), $r), true));
            if ($j === false) {
                continue;
            }
            $awal = array_search('Inisiatif', array_map(fn ($v) => trim((string) $v), $rows[$j]));
            // Kolom nama, profil & tanggal dari baris judul di atasnya
            $atas = array_map(fn ($v) => strtolower(preg_replace('/\s+/', ' ', (string) $v)), $rows[$j - 1]);
            $kol = fn ($kata) => collect($atas)->search(fn ($v) => str_contains($v, $kata));
            [$kNama, $kProfil, $kTgl] = [$kol('nama karyawan'), $kol('profil'), $kol('last')];
            foreach (array_slice($rows, $j + 1) as $r) {
                $nama = trim((string) ($r[$kNama] ?? ''));
                if (!$nama || !is_numeric($r[$awal] ?? null) || str_starts_with($nama, '#')) {
                    continue;
                }
                $staff = $this->staffBernama($nama);
                if (!$staff?->nip) {
                    $hasil['tak_cocok'][] = "$nama ($tab)";
                    continue;
                }
                $skor = array_combine([...array_keys(Disc::KOMITMEN), ...array_keys(Disc::KOMPETENSI)],
                    array_map(fn ($i) => is_numeric($r[$awal + $i] ?? null) ? (int) $r[$awal + $i] : null, range(0, 7)));
                Disc::updateOrCreate(['nip' => $staff->nip], $skor + [
                    'tim' => $tim, 'profil' => trim((string) ($r[$kProfil] ?? '')) ?: null, 'dinilai_at' => $kTgl !== false ? $this->tgl($r[$kTgl] ?? null) : null,
                ]);
                $hasil['orang']++;
            }
        }
        return $hasil;
    }

    /**
     * Tab per dosen (Eka P, Jaka, ...) di Dashboard Dosen + tab "Rencana Sertifikasi Dosen".
     * Tata letak tiap tab berbeda, jadi bagian dicari lewat judul kolomnya.
     */
    public function rapor(string $file): array
    {
        $semua = IOFactory::createReaderForFile($file)->listWorksheetNames($file);
        $tabs = array_slice($semua, array_search('KPI', $semua) + 1, array_search('Resume BD', $semua) - array_search('KPI', $semua) - 1);
        $buku = $this->tab($file, [...$tabs, 'Rencana Sertifikasi Dosen']);
        $hasil = ['dosen' => 0, 'capaian' => 0, 'tak_cocok' => []];

        foreach ($tabs as $tab) {
            $sheet = $buku->getSheetByName($tab);
            $rows = $this->baris($sheet);
            $nama = collect($rows)->first(fn ($r) => trim((string) ($r[0] ?? '')) === 'Nama Lengkap')[1] ?? $tab;
            if (!$dosen = $this->dosenBernama($nama) ?? $this->dosenSingkat($tab, null)) {
                $hasil['tak_cocok'][] = "$nama (tab $tab)";
                continue;
            }
            $hasil['dosen']++;
            $tautan = fn ($r, $c) => $sheet->getCell([$c + 1, $r + 1])->getHyperlink()->getUrl() ?: null;
            $bagian = [
                'roadmap' => ['Rencana Kegiatan', ['tahun' => ['Tahun'], 'bulan' => ['Bulan'], 'status' => ['Capaian'], 'keterangan' => ['Keterangan']]],
                'sertifikat' => ['Nama Sertifikasi', ['sumber' => ['Penyelenggara'], 'tahun' => ['Tahun', 'Tanggal Sertifikat'], 'link' => ['Link Sertifikat']]],
                'prestasi' => ['Capaian Prestasi', ['judul' => ['Judul'], 'tahun' => ['Tahun'], 'sumber' => ['Penyelenggara'], 'link' => ['Link Sertifikat']]],
                'publikasi' => ['Judul Publikasi', ['tahun' => ['Tahun'], 'status' => ['Peringkat Sinta'], 'link' => ['Link Publikasi']]],
            ];
            foreach ($bagian as $jenis => [$jangkar, $kolom]) {
                foreach ($this->bacaBagian($rows, $jangkar, $kolom) as [$baris, $isi, $kolLink]) {
                    if ($jenis === 'prestasi') {
                        $isi['status'] = $isi['judul_jangkar']; // jenis prestasi: Buku, HKI, Hibah, ...
                        $isi['judul'] = $isi['judul'] ?: $isi['judul_jangkar'];
                    } else {
                        $isi['judul'] = $isi['judul_jangkar'];
                    }
                    $isi['link'] = $kolLink !== null ? ($tautan($baris, $kolLink) ?? (str_starts_with((string) $isi['link'], 'http') ? $isi['link'] : null)) : null;
                    $hasil['capaian'] += $this->simpanCapaian($dosen, $jenis, $isi);
                }
            }
            // Rekognisi: kolom tanggal + uraian di bawah judul "Rekognisi Dosen"
            foreach ($rows as $i => $r) {
                if (($c = array_search('Rekognisi Dosen', array_map(fn ($v) => trim((string) $v), $r))) === false) {
                    continue;
                }
                foreach (array_slice($rows, $i + 1, 12) as $x) {
                    $uraian = trim((string) ($x[$c + 1] ?? ''));
                    if (preg_match('/^(Matakuliah|Publikasi|Skor|Prestasi)/i', trim((string) ($x[$c] ?? '')))) {
                        break;
                    }
                    if ($uraian) {
                        $hasil['capaian'] += $this->simpanCapaian($dosen, 'rekognisi', ['judul' => $uraian, 'tahun' => $this->teksTanggal($x[$c] ?? null)]);
                    }
                }
            }
            // DISC di profil dosen: dipakai bila pegawainya belum dipetakan di file HRD
            $disc = collect($rows)->first(fn ($r) => trim((string) ($r[0] ?? '')) === 'DISC')[1] ?? null;
            if ($disc && ($nip = $dosen->staff?->nip)) {
                Disc::firstOrCreate(['nip' => $nip], ['profil' => trim($disc), 'tim' => 'Prodi ' . $dosen->program_studi]);
            }
        }

        // Rencana sertifikasi → roadmap ("Mei 2026")
        foreach (array_slice($this->baris($buku->getSheetByName('Rencana Sertifikasi Dosen')), 1) as $r) {
            if (!($r[1] ?? null) || !$dosen = $this->dosenBernama($r[0])) {
                continue;
            }
            preg_match('/(\p{L}+)\s+(\d{4})/u', (string) ($r[4] ?? ''), $w);
            $hasil['capaian'] += $this->simpanCapaian($dosen, 'roadmap', [
                'tahun' => $w[2] ?? null, 'bulan' => $w[1] ?? null, 'judul' => 'Sertifikasi kompetensi: ' . trim($r[1]), 'sumber' => $r[2] ?? null,
                'keterangan' => is_numeric($r[3] ?? null) ? 'Biaya Rp ' . number_format($r[3], 0, ',', '.') : null,
            ]);
        }
        return $hasil;
    }

    /**
     * Baris-baris satu bagian tabel: cari sel judul $jangkar, kolom lain = judul terdekat di baris yang sama
     * (bagian lain bisa sebaris, mis. "Tahun" muncul dua kali). Berhenti di judul bagian berikutnya / 4 baris kosong.
     * @return array<int, array{0:int, 1:array, 2:?int}> [nomor baris, isi, kolom link]
     */
    private function bacaBagian(array $rows, string $jangkar, array $kolom): array
    {
        $hasil = [];
        foreach ($rows as $i => $r) {
            $judul = array_map(fn ($v) => trim(preg_replace('/\s+/', ' ', (string) $v)), $r);
            if (($cj = array_search($jangkar, $judul, true)) === false) {
                continue;
            }
            $peta = ['judul_jangkar' => $cj];
            foreach ($kolom as $f => $label) {
                $calon = array_keys(array_filter($judul, fn ($v) => in_array($v, $label, true)));
                usort($calon, fn ($a, $b) => abs($a - $cj) <=> abs($b - $cj));
                $peta[$f] = $calon[0] ?? null;
            }
            $kosong = 0;
            foreach (array_slice($rows, $i + 1, null, true) as $n => $x) {
                $nilai = array_map(fn ($c) => $c === null ? null : (is_string($x[$c] ?? null) ? trim($x[$c]) : ($x[$c] ?? null)), $peta);
                // Judul bagian berikutnya. Di kolom isi (judul_jangkar) harus persis label judul, karena isinya sendiri
                // bisa diawali kata yang sama ("Publikasi Jurnal Sinta 2")
                $lain = collect($nilai)->except('judul_jangkar')->filter(fn ($v) => is_string($v));
                if (in_array($nilai['judul_jangkar'], ['Rencana Kegiatan', 'Nama Sertifikasi', 'Capaian Prestasi', 'Judul Publikasi', 'Judul', 'Tahun', 'No'], true)
                    || $lain->contains(fn ($v) => preg_match('/^(Tahun|No|Judul|Prestasi Dosen|Publikasi (Dosen|Penelitan)|Sertifikasi dan Training|Matakuliah|Skor Capaian|Rekognisi Dosen|Capaian Prestasi)\b/i', $v))) {
                    break;
                }
                if ($nilai['judul_jangkar'] === null || $nilai['judul_jangkar'] === '') {
                    if (++$kosong >= 4) {
                        break;
                    }
                    continue;
                }
                $kosong = 0;
                $nilai['tahun'] = $this->teksTanggal($nilai['tahun'] ?? null);
                $hasil[] = [$n, $nilai, $peta['link'] ?? null];
            }
        }
        return $hasil;
    }

    private function simpanCapaian(Dosen $dosen, string $jenis, array $isi): int
    {
        $bulan = $isi['bulan'] ?? null;
        $bulan = is_numeric($bulan) ? (int) $bulan : (array_search(ucfirst(strtolower(trim((string) $bulan))), CapaianDosen::BULAN) ?: null);
        $judul = mb_substr(trim((string) ($isi['judul'] ?? '')), 0, 500);
        if (!$judul || $judul === '-') {
            return 0;
        }
        $teks = fn ($k, $n = 255) => ($v = trim((string) ($isi[$k] ?? ''))) !== '' && $v !== '-' ? mb_substr($v, 0, $n) : null;
        CapaianDosen::updateOrCreate(['dosen_id' => $dosen->id, 'jenis' => $jenis, 'judul' => $judul, 'tahun' => $teks('tahun', 40)], [
            'bulan' => $bulan, 'sumber' => $teks('sumber'), 'status' => $teks('status'), 'keterangan' => $teks('keterangan', 500), 'link' => $teks('link', 500),
        ]);
        return 1;
    }

    // Tahun/tanggal apa adanya: serial Excel → "2024-02-29", 2024.0 → "2024"
    private function teksTanggal($v): ?string
    {
        return match (true) {
            $v === null || $v === '' => null,
            is_numeric($v) && $v > 1900 && $v < 2100 => (string) (int) $v,
            is_numeric($v) => $this->tgl($v),
            default => trim((string) $v),
        };
    }

    // Pegawai dari nama (tanpa gelar): sama persis, atau beda ketik kecil bila hanya satu kandidat; utamakan aktif
    private function staffBernama(string $nama): ?Staff
    {
        $inti = Sinkron::namaInti($nama);
        $semua = Staff::whereNotNull('nip')->orderByDesc('aktif')->get();
        $mirip = $semua->filter(fn ($s) => levenshtein(Sinkron::namaInti($s->nama), $inti) <= 2)->unique('nip');
        return $semua->first(fn ($s) => Sinkron::namaInti($s->nama) === $inti) ?? ($mirip->count() === 1 ? $mirip->first() : null)
            ?? ($this->dosenBernama($nama)?->staff);
    }

    // Tab Rasio: blok "Rasio Dosen TA xxxx/yyyy" berisi prodi, jumlah dosen, jumlah mahasiswa, rasio, target
    public function rasio(string $file): int
    {
        $rows = $this->baris($this->tab($file, ['Rasio'])->getSheetByName('Rasio'));
        $n = 0;
        $atas = collect($rows)->search(fn ($r) => collect($r)->contains(fn ($v) => str_contains((string) $v, 'Rasio Dosen TA')));
        foreach ($atas === false ? [] : $rows[$atas] as $c => $v) {
            if (!preg_match('#TA (\d{4}/\d{4})#', (string) $v, $ta)) {
                continue;
            }
            foreach (array_slice($rows, $atas + 2) as $r) {
                if (!($prodi = $this->prodi($r[$c] ?? null)) || !is_numeric($r[$c + 2] ?? null)) {
                    continue;
                }
                RasioProdi::updateOrCreate(['program_studi' => $prodi, 'tahun_ajaran' => $ta[1]],
                    ['jumlah_mahasiswa' => (int) $r[$c + 2], 'target' => (int) ($r[$c + 4] ?: 22)]);
                $n++;
            }
        }
        return $n;
    }

    // Hanya tab yang dibutuhkan, tanpa format: workbook dashboard puluhan tab & grafik terlalu berat dibuka utuh
    private function tab(string $file, array $tabs)
    {
        $tabs = array_is_list($tabs) ? $tabs : array_keys($tabs);
        $baca = IOFactory::createReaderForFile($file);
        $baca->setLoadSheetsOnly($tabs);
        return $baca->load($file);
    }

    // Nilai tersimpan (bukan dihitung ulang): sel berisi formula Google Sheets (QUERY, IMPORTRANGE) jadi #NAME? bila dihitung
    private function baris($sheet): array
    {
        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $r = [];
            foreach ($row->getCellIterator() as $cell) {
                $r[] = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
            }
            $rows[] = $r;
        }
        return $rows;
    }

    // "S1 Sistem Informasi" / "SI Bisnis Digital" → nama prodi HRD; prodi D3 lama dibiarkan apa adanya
    private function prodi(?string $v): ?string
    {
        $v = trim((string) $v);
        foreach (Dosen::PRODI as $p) {
            if ($v && str_ends_with($v, $p)) {
                return $p;
            }
        }
        return $v ?: null;
    }

    // Nama tanpa gelar; utamakan dosen aktif
    private function dosenBernama(?string $nama): ?Dosen
    {
        $inti = Sinkron::namaInti((string) $nama);
        $semua = Dosen::orderByDesc('aktif')->get();
        // ponytail: salah ketik kecil (Rendi/Rendy) ditoleransi bila hanya satu kandidat
        $mirip = $semua->filter(fn ($d) => levenshtein(Sinkron::namaInti($d->nama), $inti) <= 2);
        return $semua->first(fn ($d) => Sinkron::namaInti($d->nama) === $inti) ?? ($mirip->count() === 1 ? $mirip->first() : null);
    }

    // Tanggal dari Excel: serial, DateTime, "2024-01-06 ...", atau "14/09/2013"
    private function tgl($v): ?string
    {
        return rescue(fn () => match (true) {
            $v === null || $v === '' => null,
            $v instanceof \DateTimeInterface => Carbon::instance($v)->toDateString(),
            is_numeric($v) => Carbon::instance(TglExcel::excelToDateTimeObject($v))->toDateString(),
            (bool) preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', trim($v)) => Carbon::createFromFormat('d/m/Y', trim($v))->toDateString(),
            default => Carbon::parse($v)->toDateString(),
        }, null, false);
    }
}
