<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notifikasi kini lewat POST /notifikasi eUniversity (cukup username), chat ID lokal tidak dipakai
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akuns', fn (Blueprint $t) => $t->dropColumn('telegram_chat_id'));
    }

    public function down(): void
    {
        Schema::table('akuns', fn (Blueprint $t) => $t->string('telegram_chat_id', 32)->nullable());
    }
};
