<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Login NIP/NIDN + OTP Telegram (meniru BAAK). Chat ID Telegram = isian HRD, tidak ditimpa sinkron.
return new class extends Migration
{
    public function up()
    {
        foreach (['staffs', 'dosens'] as $tabel) {
            if (!Schema::hasColumn($tabel, 'telegram')) {
                Schema::table($tabel, fn (Blueprint $t) => $t->string('telegram', 32)->nullable()->after('no_hp'));
            }
        }

        // Akun hasil login OTP tidak punya id akun eUniversity (ALTER langsung: ->change() butuh doctrine/dbal)
        DB::statement('ALTER TABLE ' . DB::getTablePrefix() . 'akuns MODIFY api_id BIGINT UNSIGNED NULL');

        Schema::create('otp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('dosen_id')->nullable()->index();
            $table->string('kode_hash');                       // bcrypt, kode asli tidak disimpan
            $table->unsignedTinyInteger('percobaan')->default(0); // salah tebak; maks 5
            $table->timestamp('kedaluwarsa_at');
            $table->timestamp('dipakai_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp');
        Schema::table('staffs', fn (Blueprint $t) => $t->dropColumn('telegram'));
        Schema::table('dosens', fn (Blueprint $t) => $t->dropColumn('telegram'));
    }
};
