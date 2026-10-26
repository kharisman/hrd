<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Semua rujukan ke data eUniversity lewat kunci (foreign key), bukan teks:
// - disc, kehadiran: nip (teks) → staff_id (staffs.api_id). Satu orang = akun staff utamanya (aktif dulu, id terkecil).
// - dosens, mk_inti, rasio_prodi, bimbingan: + prodi_id (prodi.api_id); nama prodi tetap sebagai label
// - kurikulum: master dari /kurikulum; mk_inti & angkatan merujuk kurikulum.api_id
// - mengajar → dosens.api_id & periode_krs.api_id; bimbingan → periode_krs.api_id; angkatan → prodi.api_id
return new class extends Migration
{
    public function up(): void
    {
        $p = DB::getTablePrefix();

        // ---- Orang: nip → staff_id ----
        foreach (['disc', 'kehadiran'] as $t) {
            Schema::table($t, fn (Blueprint $b) => $b->foreignId('staff_id')->nullable()->after('id')->constrained('staffs')->cascadeOnDelete());
            DB::statement("UPDATE {$p}{$t} x SET staff_id = (SELECT s.id FROM {$p}staffs s WHERE s.nip = x.nip ORDER BY s.aktif DESC, s.id LIMIT 1)");
            DB::table($t)->whereNull('staff_id')->delete(); // tidak ada (audit: 0 baris), jaga-jaga
        }
        Schema::table('disc', function (Blueprint $b) {
            $b->dropUnique(['nip']);
            $b->dropColumn('nip');
            $b->unique('staff_id');
        });
        Schema::table('kehadiran', function (Blueprint $b) {
            $b->dropUnique(['nip', 'tahun', 'bulan']);
            $b->dropColumn('nip');
            $b->unique(['staff_id', 'tahun', 'bulan']);
        });
        // ponytail: staff_id tetap nullable di DB (MySQL menolak MODIFY kolom ber-FK); kode selalu mengisinya

        // ---- Prodi: + prodi_id ----
        foreach (['dosens' => 'program_studi', 'mk_inti' => 'program_studi', 'rasio_prodi' => 'program_studi', 'bimbingan' => 'program_studi'] as $t => $kol) {
            Schema::table($t, fn (Blueprint $b) => $b->foreignId('prodi_id')->nullable()->after($kol)->constrained('prodi')->nullOnDelete());
            // "D3 Akuntansi" → prodi Akuntansi jenjang D3; nama lain → prodi sarjana (S1) bernama sama, lalu nama apa pun
            DB::statement("UPDATE {$p}{$t} x SET prodi_id = COALESCE(
                (SELECT r.id FROM {$p}prodi r WHERE x.{$kol} LIKE 'D3 %' AND r.nama = SUBSTRING(x.{$kol}, 4) AND r.jenjang = 'Diploma Tiga' LIMIT 1),
                (SELECT r.id FROM {$p}prodi r WHERE r.nama = x.{$kol} AND r.jenjang = 'Strata Satu' LIMIT 1),
                (SELECT r.id FROM {$p}prodi r WHERE r.nama = x.{$kol} LIMIT 1)) WHERE x.{$kol} IS NOT NULL");
        }

        // ---- Kurikulum: master + rujukan ----
        Schema::create('kurikulum', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('api_id')->unique();
            $t->unsignedBigInteger('prodi_api_id')->nullable();
            $t->string('kode', 30)->nullable();
            $t->string('nama')->nullable();
            $t->unsignedTinyInteger('masa_studi')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
            $t->foreign('prodi_api_id')->references('api_id')->on('prodi')->nullOnDelete();
        });
        // Isi sementara dari rujukan yang sudah ada; nama & detail dilengkapi hrd:sync
        DB::statement("INSERT INTO {$p}kurikulum (api_id, kode, created_at, updated_at)
            SELECT id, MAX(kode), NOW(), NOW() FROM (
                SELECT id_kurikulum_api id, kurikulum kode FROM {$p}mk_inti WHERE id_kurikulum_api IS NOT NULL
                UNION ALL SELECT id_kurikulum_api, kurikulum FROM {$p}angkatan WHERE id_kurikulum_api IS NOT NULL) k GROUP BY id");
        Schema::table('mk_inti', fn (Blueprint $b) => $b->foreign('id_kurikulum_api')->references('api_id')->on('kurikulum')->nullOnDelete());
        Schema::table('angkatan', function (Blueprint $b) {
            $b->foreign('id_kurikulum_api')->references('api_id')->on('kurikulum')->nullOnDelete();
            $b->foreign('prodi_api_id')->references('api_id')->on('prodi')->cascadeOnDelete();
        });

        // ---- Mengajar & bimbingan → dosen & periode (kunci API) ----
        Schema::table('mengajar', function (Blueprint $b) {
            $b->foreign('dosen_api_id')->references('api_id')->on('dosens')->cascadeOnDelete();
            $b->foreign('id_krs')->references('api_id')->on('periode_krs')->cascadeOnDelete();
        });
        Schema::table('bimbingan', fn (Blueprint $b) => $b->foreign('id_krs')->references('api_id')->on('periode_krs')->cascadeOnDelete());
    }
};
