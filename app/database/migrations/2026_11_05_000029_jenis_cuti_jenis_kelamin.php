<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jenis cuti khusus satu jenis kelamin (L/P, null = semua): mis. Cuti Melahirkan hanya perempuan
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_cuti', fn (Blueprint $t) => $t->char('jenis_kelamin', 1)->nullable()->after('kuota'));
        DB::table('jenis_cuti')->where('nama', 'like', '%melahirkan%')->update(['jenis_kelamin' => 'P']);
    }

    public function down(): void
    {
        Schema::table('jenis_cuti', fn (Blueprint $t) => $t->dropColumn('jenis_kelamin'));
    }
};
