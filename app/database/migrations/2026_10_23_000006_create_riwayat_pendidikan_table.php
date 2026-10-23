<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Rekam jejak pendidikan pegawai/dosen, termasuk studi lanjut yang sedang berjalan.
        // Pemilik: staff_id dan/atau dosen_id (dosen non-staff hanya dosen_id).
        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('dosen_id')->nullable()->index();
            $table->string('jenjang', 10); // SMA, D3, S1, S2, S3, Profesi
            $table->string('institusi');
            $table->string('program_studi')->nullable();
            $table->unsignedSmallInteger('tahun_masuk')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable(); // studi lanjut: target lulus
            $table->string('status', 30)->default('Lulus'); // Lulus / Sedang Studi Lanjut / Tidak Selesai
            $table->string('pembiayaan', 30)->nullable(); // Mandiri / Beasiswa Institusi / Beasiswa Luar
            $table->string('berkas')->nullable(); // path ijazah/SK di storage public
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riwayat_pendidikan');
    }
};
