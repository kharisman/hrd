<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kalender libur nasional & cuti bersama (SK Pimpinan): cuti bersama memotong jatah cuti tahunan
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->unique();
            $t->string('nama', 150);
            $t->boolean('cuti_bersama')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
