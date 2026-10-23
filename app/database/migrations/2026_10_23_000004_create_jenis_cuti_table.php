<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Konfigurasi jenis cuti. kuota = jatah hari per tahun (null = tanpa jatah).
        // Jatah berlaku per tahun kalender, tidak diakumulasi ke tahun berikutnya.
        Schema::create('jenis_cuti', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->unsignedSmallInteger('kuota')->nullable();
            $table->string('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        DB::table('jenis_cuti')->insert(array_map(fn ($r) => $r + ['aktif' => true, 'created_at' => now(), 'updated_at' => now()], [
            ['nama' => 'Cuti Tahunan', 'kuota' => 12, 'keterangan' => null],
            ['nama' => 'Cuti Sakit', 'kuota' => 12, 'keterangan' => 'Lampirkan surat dokter'],
            ['nama' => 'Cuti Melahirkan', 'kuota' => 90, 'keterangan' => null],
            ['nama' => 'Cuti Alasan Penting', 'kuota' => 3, 'keterangan' => 'Duka, pernikahan, dll.'],
            ['nama' => 'Izin', 'kuota' => null, 'keterangan' => 'Tidak memotong jatah'],
        ]));

        // Tabel cuti masih kosong: ganti kolom teks jenis menjadi relasi
        Schema::table('cuti', function (Blueprint $table) {
            $table->dropColumn('jenis');
            $table->foreignId('jenis_cuti_id')->after('nip_id')->constrained('jenis_cuti');
        });
    }

    public function down()
    {
        Schema::table('cuti', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_cuti_id');
            $table->string('jenis')->after('nip_id');
        });
        Schema::dropIfExists('jenis_cuti');
    }
};
