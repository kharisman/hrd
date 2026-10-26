<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Angkatan & periode KRS dari API eUniversity (hrd:sync):
// - angkatan: kohort per prodi + kurikulum yang dipakainya → kurikulum berlaku di matriks MK
// - periode_krs: periode kuliah (ganjil/genap/pendek) → pilihan tahun ajaran rasio
// - rasio_prodi.per_angkatan: jumlah mahasiswa aktif per angkatan {kode angkatan: jumlah}
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angkatan', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('api_id')->unique();
            $t->unsignedBigInteger('prodi_api_id')->index();
            $t->unsignedSmallInteger('kode');        // nomor angkatan dalam prodi
            $t->string('periode', 20)->nullable();   // tahun ajaran masuk, mis. 2022/2023
            $t->unsignedTinyInteger('semester')->nullable();
            $t->unsignedBigInteger('id_kurikulum_api')->nullable()->index();
            $t->string('kurikulum')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
        Schema::create('periode_krs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('api_id')->unique();
            $t->string('tipe', 10);
            $t->string('tahun_akademik', 9);
            $t->date('mulai')->nullable();
            $t->date('selesai')->nullable();
            $t->timestamps();
        });
        Schema::table('rasio_prodi', fn (Blueprint $t) => $t->json('per_angkatan')->nullable()->after('jumlah_mahasiswa'));
    }
};
