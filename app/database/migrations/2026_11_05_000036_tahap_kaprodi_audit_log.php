<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Dokumen alur: data dosen divalidasi Kaprodi lalu disetujui HRD; audit trail aksi pengguna
return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_pengajuan')->where('kode', 'capaian')->update(['tahapan' => json_encode(['kaprodi', 'hrd'])]);

        Schema::create('audit_log', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('akun_id')->nullable()->index();
            $t->string('nama', 150)->nullable();
            $t->string('aksi', 100)->index();           // login / nama route
            $t->string('metode', 8);
            $t->string('url', 500);
            $t->json('data')->nullable();               // isian form tanpa password/token, berkas hanya nama
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        DB::table('jenis_pengajuan')->where('kode', 'capaian')->update(['tahapan' => json_encode(['atasan', 'hrd'])]);
    }
};
