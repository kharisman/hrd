<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\JenisCutiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web,pegawai')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:web,pegawai')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    // Pegawai = staff eUniversity (URL /pegawai, nama route staff.*)
    Route::post('pegawai/atasan', [StaffController::class, 'aturAtasan'])->name('staff.atasan');
    Route::resource('pegawai', StaffController::class)->parameters(['pegawai' => 'staff'])->names('staff');
    Route::post('dosen/sinkron', [DosenController::class, 'sinkron'])->name('dosen.sinkron');
    Route::resource('dosen', DosenController::class);
    Route::get('riwayat/impor', [RiwayatController::class, 'imporForm'])->name('riwayat.impor');
    Route::post('riwayat/impor', [RiwayatController::class, 'impor']);
    Route::get('riwayat/template', [RiwayatController::class, 'template'])->name('riwayat.template');
    Route::get('riwayat/{riwayat}/berkas', [RiwayatController::class, 'berkas'])->name('riwayat.berkas');
    Route::resource('riwayat', RiwayatController::class)->except('show');
    Route::resource('cuti', CutiController::class)->only('index', 'create', 'store')->parameters(['cuti' => 'cuti']);
    Route::resource('jenis-cuti', JenisCutiController::class)->only('index', 'store', 'update');
    Route::prefix('laporan')->name('laporan.')->controller(LaporanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('cuti', 'cuti')->name('cuti');
        Route::get('pegawai', 'pegawai')->name('pegawai');
        Route::get('dosen', 'dosen')->name('dosen');
        Route::get('pendidikan', 'pendidikan')->name('pendidikan');
    });

    Route::prefix('pengaturan')->name('pengaturan.')->controller(PengaturanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('admin', 'admin')->name('admin');
        Route::post('admin', 'adminStore')->name('admin.store');
        Route::put('admin/{user}', 'adminUpdate')->name('admin.update');
        Route::delete('admin/{user}', 'adminDestroy')->name('admin.destroy');
        Route::get('sinkron', 'sinkron')->name('sinkron');
        Route::get('api', 'api')->name('api');
        Route::post('api/cek', 'cekApi')->name('api.cek');
    });

    Route::post('cuti/{cuti}/proses', [CutiController::class, 'proses'])->name('cuti.proses');
});
