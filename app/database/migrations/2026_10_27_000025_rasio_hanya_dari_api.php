<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jumlah mahasiswa hanya dari eUniversity: data isian/impor manual dihapus, kolom sumber & target tidak dipakai lagi
// (target jadi konstanta RasioProdi::TARGET)
return new class extends Migration
{
    public function up(): void
    {
        DB::table('rasio_prodi')->where('sumber', '!=', 'api')->delete();
        Schema::table('rasio_prodi', fn (Blueprint $t) => $t->dropColumn(['sumber', 'target']));
    }
};
