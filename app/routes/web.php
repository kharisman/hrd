<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardDosenController;
use App\Http\Controllers\DashboardPegawaiController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\JenisCutiController;
use App\Http\Controllers\JenisPengajuanController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RaporDosenController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

// Halaman publik tujuan QR tanda tangan (tanpa login). Dibatasi 30 permintaan/menit supaya kode tidak bisa ditebak beruntun.
Route::get('validasi/{kode}', [PengajuanController::class, 'validasi'])->middleware('throttle:30,1')->name('validasi');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    // Login NIP/NIDN + OTP Telegram
    Route::post('login/otp', [OtpController::class, 'kirim'])->name('login.otp.kirim');
    Route::get('login/otp', [OtpController::class, 'formKode'])->name('login.otp');
    Route::post('login/otp/verifikasi', [OtpController::class, 'verifikasi'])->middleware('throttle:10,1')->name('login.otp.verifikasi');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('dashboard/dosen', DashboardDosenController::class)->name('dashboard.dosen');
    Route::post('dashboard/dosen/rasio', [DashboardDosenController::class, 'simpanRasio'])->name('dashboard.dosen.rasio');
    Route::get('dashboard/dosen/mk', [DashboardDosenController::class, 'mk'])->name('dashboard.dosen.mk');
    Route::post('dashboard/dosen/mk', [DashboardDosenController::class, 'simpanMk'])->name('dashboard.dosen.mk.simpan');
    Route::post('dashboard/dosen/mk/tambah', [DashboardDosenController::class, 'tambahMk'])->name('dashboard.dosen.mk.tambah');
    Route::delete('dashboard/dosen/mk/{mk}', [DashboardDosenController::class, 'hapusMk'])->name('dashboard.dosen.mk.hapus');
    Route::get('dashboard/dosen/kompetensi', [DashboardDosenController::class, 'kompetensi'])->name('dashboard.dosen.kompetensi');
    Route::get('dashboard/dosen/rapor', [RaporDosenController::class, 'index'])->name('dashboard.dosen.rapor.index');
    Route::get('dashboard/dosen/rapor/{dosen}', [RaporDosenController::class, 'show'])->name('dashboard.dosen.rapor');
    Route::post('dashboard/dosen/rapor/{dosen}', [RaporDosenController::class, 'simpan'])->name('dashboard.dosen.rapor.simpan');
    Route::patch('dashboard/dosen/capaian/{capaian}', [RaporDosenController::class, 'status'])->name('dashboard.dosen.capaian.status');
    Route::delete('dashboard/dosen/capaian/{capaian}', [RaporDosenController::class, 'hapus'])->name('dashboard.dosen.capaian.hapus');
    Route::post('dashboard/dosen/kompetensi/{dosen}', [DashboardDosenController::class, 'simpanKompetensi'])->name('dashboard.dosen.kompetensi.simpan');
    Route::get('dashboard/pegawai', DashboardPegawaiController::class)->name('dashboard.pegawai');
    Route::post('dashboard/pegawai/kehadiran', [DashboardPegawaiController::class, 'simpan'])->name('dashboard.pegawai.simpan');
    Route::post('dashboard/pegawai/disc', [DashboardPegawaiController::class, 'simpanDisc'])->name('dashboard.pegawai.disc');
    // Pegawai = staff eUniversity (URL /pegawai, nama route staff.*)
    Route::get('pegawai/atasan', [StaffController::class, 'atasan'])->name('staff.atasan');
    Route::post('pegawai/atasan', [StaffController::class, 'aturAtasan'])->name('staff.atasan.simpan');
    Route::resource('pegawai', StaffController::class)->parameters(['pegawai' => 'staff'])->names('staff');
    Route::post('dosen/sinkron', [DosenController::class, 'sinkron'])->name('dosen.sinkron');
    Route::resource('dosen', DosenController::class);
    Route::get('riwayat/impor', [RiwayatController::class, 'imporForm'])->name('riwayat.impor');
    Route::post('riwayat/impor', [RiwayatController::class, 'impor']);
    Route::get('riwayat/template', [RiwayatController::class, 'template'])->name('riwayat.template');
    Route::get('riwayat/{riwayat}/berkas', [RiwayatController::class, 'berkas'])->name('riwayat.berkas');
    Route::resource('riwayat', RiwayatController::class)->except('show');
    Route::prefix('laporan')->name('laporan.')->controller(LaporanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('cuti', 'cuti')->name('cuti');
        Route::get('pegawai', 'pegawai')->name('pegawai');
        Route::get('dosen', 'dosen')->name('dosen');
        Route::get('pendidikan', 'pendidikan')->name('pendidikan');
        Route::get('pengajuan', 'pengajuan')->name('pengajuan');
    });

    Route::prefix('pengaturan')->name('pengaturan.')->controller(PengaturanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('dokumen', 'dokumen')->name('dokumen');
        Route::post('dokumen', 'simpanDokumen')->name('dokumen.simpan');
        Route::get('pejabat', 'pejabat')->name('pejabat');
        Route::post('pejabat', 'tambahPejabat')->name('pejabat.tambah');
        Route::delete('pejabat/{pejabat}', 'hapusPejabat')->name('pejabat.hapus');
        Route::get('sinkron', 'sinkron')->name('sinkron');
        Route::get('api', 'api')->name('api');
        Route::post('api/cek', 'cekApi')->name('api.cek');
    });

    Route::resource('pengajuan', PengajuanController::class)->only('index', 'create', 'store', 'show');
    Route::post('pengajuan/{pengajuan}/proses', [PengajuanController::class, 'proses'])->name('pengajuan.proses');
    Route::post('pengajuan/{pengajuan}/batal', [PengajuanController::class, 'batal'])->name('pengajuan.batal');
    Route::get('pengajuan/{pengajuan}/pdf', [PengajuanController::class, 'pdf'])->name('pengajuan.pdf');
    Route::get('pengajuan/{pengajuan}/berkas/{key}', [PengajuanController::class, 'berkas'])->name('pengajuan.berkas');
    Route::resource('jenis-cuti', JenisCutiController::class)->only('index', 'store', 'update');
    Route::resource('jenis-pengajuan', JenisPengajuanController::class)->except('show', 'destroy');

});
