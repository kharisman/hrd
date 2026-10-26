<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OTP & chat ID Telegram kini diurus eUniversity (/auth/otp/*), salinan lokal tidak dipakai lagi
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('otp');
        foreach (['staffs', 'dosens'] as $t) {
            if (Schema::hasColumn($t, 'telegram')) {
                Schema::table($t, fn (Blueprint $b) => $b->dropColumn('telegram'));
            }
        }
    }
};
