<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Scope API eUniversity milik akun (mis. dosen.create, staff.update, *) → menentukan tombol yang aktif
        Schema::table('akuns', fn (Blueprint $t) => $t->json('scopes')->nullable()->after('roles'));
    }

    public function down()
    {
        Schema::table('akuns', fn (Blueprint $t) => $t->dropColumn('scopes'));
    }
};
