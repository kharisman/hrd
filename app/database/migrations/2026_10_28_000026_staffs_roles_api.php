<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Role eUniversity per pegawai (gabungan /role-map berdasarkan divisi × level dan role akun di /api-users),
// disinkron supaya akses & alur persetujuan HRD bisa memakai role walau orangnya belum pernah login.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', fn (Blueprint $t) => $t->json('roles')->nullable()->after('level'));
    }
};
