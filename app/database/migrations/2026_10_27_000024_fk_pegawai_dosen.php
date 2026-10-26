<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rujukan lokal ke pegawai/dosen (yang tertaut ke eUniversity lewat api_id) dijaga foreign key.
// Pengajuan: restrict → pegawai yang punya pengajuan tidak dihapus sinkron, hanya dinonaktifkan (arsip surat utuh).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', fn (Blueprint $t) => $t->foreign('atasan_id')->references('id')->on('staffs')->nullOnDelete());
        Schema::table('dosens', fn (Blueprint $t) => $t->foreign('staff_id')->references('id')->on('staffs')->nullOnDelete());
        Schema::table('akuns', function (Blueprint $t) {
            $t->foreign('staff_id')->references('id')->on('staffs')->nullOnDelete();
            $t->foreign('dosen_id')->references('id')->on('dosens')->nullOnDelete();
        });
        Schema::table('riwayat_pendidikan', function (Blueprint $t) {
            $t->foreign('staff_id')->references('id')->on('staffs')->nullOnDelete();
            $t->foreign('dosen_id')->references('id')->on('dosens')->nullOnDelete();
        });
        Schema::table('pengajuan', fn (Blueprint $t) => $t->foreign('staff_id')->references('id')->on('staffs')->restrictOnDelete());
    }
};
