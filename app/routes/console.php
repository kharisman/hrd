<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Sinkron memakai token aplikasi HRD (POST /auth/token, cukup X-API-Key): bisa jalan terjadwal tanpa password orang.
Artisan::command('hrd:sync', function () {
    $api = app(App\Services\PctApi::class);
    $token = $api->tokenAplikasi();
    try {
        app()->instance(App\Services\PctApi::class, $api->pakaiToken($token));
        $this->info('Menarik data staff & dosen dari API eUniversity...');
        $s = app(App\Services\Sinkron::class)->jalankan();
        App\Services\SinkronAkademik::catat('pegawai_dosen', $s['staff_api'] + $s['dosen_api']);
        foreach ($s as $k => $v) {
            $this->line(str_pad($k, 24) . $v);
        }
        $this->info('Menarik data akademik (prodi, mahasiswa, kelas, kurikulum)...');
        foreach (app(App\Services\SinkronAkademik::class)->jalankan() as $k => $v) {
            $this->line(str_pad($k, 24) . $v);
        }
    } finally {
        $api->logout($token);
    }
})->purpose('Sinkron master staff & dosen dari API eUniversity (token aplikasi HRD)');

// Impor sekali jalan dari spreadsheet dashboard lama. Tanpa --simpan: hanya uji (transaksi dibatalkan).
// php artisan hrd:impor-dashboard "Dashboard HRD.xlsx" "Dashboard Dosen.xlsx" --simpan
Artisan::command('hrd:impor-dashboard {hrd} {dosen} {--simpan}', function (string $hrd, string $dosen) {
    $imp = new App\Services\ImporDashboard();
    DB::beginTransaction();
    $k = $imp->karyawan($hrd);
    $j = $imp->jafung($dosen);
    $h = $imp->kehadiran($hrd);
    $mk = $imp->penguasaanMk($dosen);
    $kp = $imp->kompetensi($dosen);
    $dc = $imp->disc($hrd);
    $rp = $imp->rapor($dosen);
    $this->info("DISC: {$dc['orang']} orang, rapor: {$rp['dosen']} dosen ({$rp['capaian']} capaian)");
    $this->info("Mata kuliah inti: {$mk['mk']}, skill kompetensi: {$kp['skill']}");
    $this->info("Pegawai diperbarui: {$k['pegawai']}, dosen (tab db): {$k['dosen']}, dosen (tab jafung): {$j['dosen']}, data kehadiran bulanan: {$h}");
    foreach ([...$k['tak_cocok'], ...$j['tak_cocok'], ...$mk['tak_cocok'], ...$kp['tak_cocok'], ...$dc['tak_cocok'], ...$rp['tak_cocok']] as $x) {
        $this->warn("  tidak cocok: $x");
    }
    $this->option('simpan') ? DB::commit() : DB::rollBack();
    $this->line($this->option('simpan') ? 'Tersimpan.' : 'Uji saja, tidak disimpan. Tambahkan --simpan untuk menyimpan.');
})->purpose('Impor data kepegawaian, jafung & rasio dari spreadsheet Dashboard HRD/Dosen');
