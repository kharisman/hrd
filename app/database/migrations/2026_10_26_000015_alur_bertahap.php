<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Alur persetujuan bebas diatur per jenis: urutan tahap dari atasan, hrd, warek1, warek2, keuangan, baak.
// - pejabat: pegawai yang memegang peran lokal (Warek I/II, Keuangan, BAAK)
// - jenis_pengajuan.tahapan: urutan tahap (menggantikan kolom alur)
// - pengajuan.tahapan: salinan urutan saat diajukan (mengubah alur jenis tidak mengganggu yang sedang berjalan)
//   + tahap_ke (posisi sekarang) + riwayat keputusan (menggantikan kolom atasan_* / hrd_*)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pejabat', function (Blueprint $t) {
            $t->id();
            $t->string('peran', 20);
            $t->foreignId('staff_id')->constrained('staffs')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['peran', 'staff_id']);
        });

        Schema::table('jenis_pengajuan', fn (Blueprint $t) => $t->json('tahapan')->nullable()->after('isian'));
        $peta = ['atasan_hrd' => ['atasan', 'hrd'], 'atasan' => ['atasan'], 'hrd' => ['hrd']];
        foreach (DB::table('jenis_pengajuan')->get(['id', 'alur']) as $j) {
            DB::table('jenis_pengajuan')->where('id', $j->id)->update(['tahapan' => json_encode($peta[$j->alur] ?? ['atasan', 'hrd'])]);
        }
        Schema::table('jenis_pengajuan', fn (Blueprint $t) => $t->dropColumn('alur'));

        Schema::table('pengajuan', function (Blueprint $t) {
            $t->json('tahapan')->nullable()->after('status');
            $t->unsignedTinyInteger('tahap_ke')->default(0)->after('tahapan');
            $t->json('riwayat')->nullable()->after('tahap_ke');
        });
        foreach (DB::table('pengajuan')->get() as $p) {
            $riwayat = [];
            foreach (['atasan', 'hrd'] as $tahap) {
                if ($p->{"{$tahap}_at"}) {
                    $riwayat[] = ['tahap' => $tahap, 'oleh' => $p->{"{$tahap}_oleh"}, 'at' => $p->{"{$tahap}_at"},
                        'catatan' => $p->{"{$tahap}_catatan"}, 'setuju' => !($p->status === 'Ditolak' && $tahap === array_key_last(array_filter(['atasan' => $p->atasan_at, 'hrd' => $p->hrd_at])))];
                }
            }
            // Tahap atasan dulu dilewati bila pegawai tak punya atasan / anonim
            $tahapan = json_decode(DB::table('jenis_pengajuan')->where('id', $p->jenis_pengajuan_id)->value('tahapan'));
            if (!$p->atasan_at && $p->status !== 'Menunggu Atasan') {
                $tahapan = array_values(array_diff($tahapan, ['atasan'])) ?: ['hrd'];
            }
            DB::table('pengajuan')->where('id', $p->id)->update([
                'tahapan' => json_encode($tahapan), 'tahap_ke' => count($riwayat), 'riwayat' => json_encode($riwayat),
            ]);
        }
        Schema::table('pengajuan', fn (Blueprint $t) => $t->dropColumn(['atasan_oleh', 'atasan_at', 'atasan_catatan', 'hrd_oleh', 'hrd_at', 'hrd_catatan']));
    }
};
