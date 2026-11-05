<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SOP cuti: lama cuti dihitung hari kerja (bisa dikoreksi HRD), jenis tertentu tetap hari kalender (Melahirkan),
// alur cuti Atasan → HRD → Rektor
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan', fn (Blueprint $t) => $t->unsignedSmallInteger('lama')->nullable()->after('tgl_selesai'));
        DB::table('pengajuan')->whereNotNull('tgl_mulai')->update(['lama' => DB::raw('DATEDIFF(tgl_selesai, tgl_mulai) + 1')]);

        Schema::table('jenis_cuti', fn (Blueprint $t) => $t->boolean('hari_kalender')->default(false)->after('jenis_kelamin'));
        DB::table('jenis_cuti')->where('nama', 'like', '%melahirkan%')->update(['hari_kalender' => true]);

        DB::table('jenis_pengajuan')->where('kode', 'cuti')->update(['tahapan' => json_encode(['atasan', 'hrd', 'rektor'])]);
    }

    public function down(): void
    {
        Schema::table('pengajuan', fn (Blueprint $t) => $t->dropColumn('lama'));
        Schema::table('jenis_cuti', fn (Blueprint $t) => $t->dropColumn('hari_kalender'));
        DB::table('jenis_pengajuan')->where('kode', 'cuti')->update(['tahapan' => json_encode(['atasan', 'hrd'])]);
    }
};
