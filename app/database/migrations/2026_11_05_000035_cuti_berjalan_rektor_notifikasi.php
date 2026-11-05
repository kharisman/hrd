<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cuti yang masih berjalan ikut alur SOP (tambah tahap Rektor di akhir) + log kiriman notifikasi per pengajuan
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pengajuan')->whereNotNull('jenis_cuti_id')->whereNotIn('status', ['Disetujui', 'Ditolak', 'Dibatalkan'])
            ->get(['id', 'tahapan'])->each(function ($p) {
                $t = json_decode($p->tahapan ?? '[]', true) ?: [];
                if (!in_array('rektor', $t)) {
                    DB::table('pengajuan')->where('id', $p->id)->update(['tahapan' => json_encode([...$t, 'rektor'])]);
                }
            });
        Schema::table('pengajuan', fn (Blueprint $t) => $t->json('notifikasi')->nullable());
    }

    public function down(): void
    {
        Schema::table('pengajuan', fn (Blueprint $t) => $t->dropColumn('notifikasi'));
    }
};
