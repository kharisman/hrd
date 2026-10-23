<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // /staff tidak punya status; diisi sinkron dari StatusLogin akun di /users (Username sama)
        Schema::table('staffs', fn (Blueprint $t) => $t->boolean('aktif')->default(true)->index()->after('level'));
    }

    public function down()
    {
        Schema::table('staffs', fn (Blueprint $t) => $t->dropColumn('aktif'));
    }
};
