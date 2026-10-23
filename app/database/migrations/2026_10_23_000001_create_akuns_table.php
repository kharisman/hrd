<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Akun eUniversity yang pernah login ke HRD (password tidak disimpan)
        Schema::create('akuns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_id')->unique();
            $table->string('username')->unique();
            $table->string('name');
            $table->json('roles');
            $table->string('peran'); // admin / dosen / staff
            $table->string('nip')->nullable();
            $table->unsignedInteger('nip_id')->nullable(); // survei.nips.id, dicocokkan lewat NIP
            $table->string('nidn')->nullable();
            $table->unsignedBigInteger('dosen_id')->nullable(); // dosens.id, dicocokkan lewat NIDN
            $table->string('divisi')->nullable();
            $table->string('level')->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('akuns');
    }
};
