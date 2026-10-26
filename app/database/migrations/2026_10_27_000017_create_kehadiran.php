<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rekap kehadiran bulanan per orang (NIP, bukan akun staff: satu orang bisa beberapa akun).
// Dulu diisi HRD lewat Google Form ke spreadsheet "Data Keterlambatan" / "Data Kehadiran".
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kehadiran', function (Blueprint $t) {
            $t->id();
            $t->string('nip', 30);
            $t->unsignedSmallInteger('tahun');
            $t->unsignedTinyInteger('bulan');
            $t->unsignedInteger('terlambat_menit')->nullable();
            $t->decimal('tidak_hadir_hari', 4, 1)->nullable();
            $t->string('keterangan')->nullable();
            $t->timestamps();
            $t->unique(['nip', 'tahun', 'bulan']);
        });
    }
};
