<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // nip_id = survei.nips.id (beda database, jadi tanpa foreign key)
        Schema::create('pegawai_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nip_id')->unique();
            $table->string('jenis_pegawai')->nullable(); // Dosen / Tendik
            $table->string('status_kepegawaian')->nullable(); // Tetap / Kontrak / Honorer
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            // Jenjang dosen
            $table->string('nidn')->nullable();
            $table->string('jabatan_fungsional')->nullable();
            $table->date('tmt_jabfung')->nullable();
            $table->unsignedSmallInteger('angka_kredit')->nullable();
            $table->boolean('serdos')->default(false);
            $table->timestamps();
        });

        Schema::create('cuti', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nip_id')->index();
            $table->string('jenis');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->text('alasan');
            $table->string('status')->default('Menunggu'); // Menunggu / Disetujui / Ditolak
            $table->string('diproses_oleh')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cuti');
        Schema::dropIfExists('pegawai_detail');
        Schema::dropIfExists('users');
    }
};
