<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Kolom wajib di API POST/PATCH /dosen
        Schema::table('dosens', function (Blueprint $table) {
            $table->string('agama', 30)->nullable()->after('tgl_lahir');
            $table->string('gol_dar', 3)->nullable()->after('agama');
        });

        // Salinan master staff eUniversity (/staff). Tambah/ubah/hapus lewat API dulu.
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_id')->nullable()->unique(); // idStaff
            $table->string('nip')->nullable()->index();
            $table->unsignedInteger('nip_id')->nullable(); // survei.nips.id bila NIP cocok
            $table->string('nama');
            $table->string('username')->nullable()->index();
            $table->unsignedInteger('id_divisi')->nullable();
            $table->string('divisi')->nullable();
            $table->unsignedInteger('id_level')->nullable();
            $table->string('level')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('gol_dar', 3)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->date('mulai_kerja')->nullable();
            $table->timestamp('disinkron_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staffs');
        Schema::table('dosens', fn (Blueprint $table) => $table->dropColumn(['agama', 'gol_dar']));
    }
};
