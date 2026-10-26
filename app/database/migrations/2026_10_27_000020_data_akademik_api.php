<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data akademik yang kini mengacu ke API eUniversity (hrd:sync):
// - prodi: master program studi (/prodi)
// - mengajar: kelas yang diampu dosen per periode (/krs/{id}/grup) → "pernah mengampu" & rapor
// - mk_inti: tautan ke kurikulum & mata kuliah API (kode, SKS)
// - dosens.jumlah_pa: mahasiswa bimbingan akademik (/dosen-pa)
// - rasio_prodi.sumber: api / manual
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodi', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('api_id')->unique();
            $t->string('kode', 10)->nullable();
            $t->string('nama');
            $t->string('jenjang')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
        Schema::create('mengajar', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_grup')->unique();
            $t->unsignedBigInteger('dosen_api_id')->index();
            $t->unsignedBigInteger('id_krs')->index();
            $t->string('periode', 30); // mis. Ganjil 2025/2026
            $t->string('kode_mk', 20)->nullable()->index();
            $t->string('nama_mk');
            $t->unsignedTinyInteger('sks')->nullable();
            $t->string('kelas', 50)->nullable();
            $t->unsignedSmallInteger('jumlah_mhs')->default(0);
            $t->timestamps();
        });
        Schema::table('mk_inti', function (Blueprint $t) {
            $t->string('kode_mk', 20)->nullable()->after('nama');
            $t->unsignedTinyInteger('sks')->nullable()->after('kode_mk');
            $t->unsignedBigInteger('id_kurikulum_api')->nullable()->after('kurikulum');
        });
        Schema::table('dosens', fn (Blueprint $t) => $t->unsignedSmallInteger('jumlah_pa')->nullable()->after('serdos'));
        Schema::table('rasio_prodi', fn (Blueprint $t) => $t->string('sumber', 10)->default('manual')->after('target'));
    }
};
