<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Admin lokal dihapus: semua login lewat akun eUniversity, admin HRD = role admin/superadmin di eUniversity.
// personal_access_tokens (bawaan Sanctum) juga tidak dipakai.
return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down()
    {
        throw new RuntimeException('Admin lokal sudah dihapus dari aplikasi.');
    }
};
