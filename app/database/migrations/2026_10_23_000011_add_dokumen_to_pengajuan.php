<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Diisi saat pengajuan disetujui (tahap akhir): nomor surat, kode validasi QR, salinan penandatangan
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->string('nomor')->nullable()->unique()->after('id');
            $table->string('kode_validasi', 40)->nullable()->unique()->after('nomor');
            $table->timestamp('disetujui_at')->nullable()->after('hrd_catatan');
            $table->json('penandatangan')->nullable()->after('disetujui_at'); // {nama, jabatan, nip} saat disetujui
        });

        // Pengaturan sederhana key-value (penandatangan dokumen, dll.)
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });
        // ttd_staff_id kosong = otomatis pegawai aktif berjabatan Rektor
        DB::table('pengaturan')->insert([
            ['kunci' => 'ttd_staff_id', 'nilai' => null, 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'kota', 'nilai' => 'Palembang', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'kode_surat', 'nilai' => 'HRD/ITB-PCT', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('pengaturan');
        Schema::table('pengajuan', fn (Blueprint $t) => $t->dropColumn(['nomor', 'kode_validasi', 'disetujui_at', 'penandatangan']));
    }
};
