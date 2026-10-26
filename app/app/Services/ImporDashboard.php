<?php

namespace App\Services;

use App\Models\Dosen;
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
