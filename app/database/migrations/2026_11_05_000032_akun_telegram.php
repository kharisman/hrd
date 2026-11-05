<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notifikasi Telegram: chat id akun (terisi setelah pengguna /start ke bot dengan kode tautan)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akuns', function (Blueprint $t) {
            $t->string('telegram_chat_id', 32)->nullable();
            $t->string('telegram_kode', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('akuns', fn (Blueprint $t) => $t->dropColumn(['telegram_chat_id', 'telegram_kode']));
    }
};
