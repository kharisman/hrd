<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Chat ID kini dari API eUniversity (GET /telegram), kode tautan /start lokal tidak dipakai
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akuns', function (Blueprint $t) {
            $t->dropUnique(['telegram_kode']);
            $t->dropColumn('telegram_kode');
        });
    }

    public function down(): void
    {
        Schema::table('akuns', fn (Blueprint $t) => $t->string('telegram_kode', 32)->nullable()->unique());
    }
};
