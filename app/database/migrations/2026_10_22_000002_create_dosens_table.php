<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dosens', function (Blueprint $table) {
            $table->id();
            // Terisi jika dosen juga pegawai (survei.nips.id). Kosong = dosen non-staff (LB, dll.)
            $table->unsignedInteger('nip_id')->nullable()->unique();
            $table->string('nama');
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('program_studi')->nullable();
            $table->string('status_dosen')->nullable(); // Tetap / Tidak Tetap / Luar Biasa
            $table->string('pendidikan_terakhir')->nullable();
            $table->string('jabatan_fungsional')->nullable();
            $table->date('tmt_jabfung')->nullable();
            $table->unsignedSmallInteger('angka_kredit')->nullable();
            $table->boolean('serdos')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Data jenjang dosen pindah ke tabel dosens
        Schema::table('pegawai_detail', function (Blueprint $table) {
            $table->dropColumn(['jenis_pegawai', 'nidn', 'jabatan_fungsional', 'tmt_jabfung', 'angka_kredit', 'serdos']);
        });
    }

    public function down()
    {
        Schema::table('pegawai_detail', function (Blueprint $table) {
            $table->string('jenis_pegawai')->nullable();
            $table->string('nidn')->nullable();
            $table->string('jabatan_fungsional')->nullable();
            $table->date('tmt_jabfung')->nullable();
            $table->unsignedSmallInteger('angka_kredit')->nullable();
            $table->boolean('serdos')->default(false);
        });
        Schema::dropIfExists('dosens');
    }
};
