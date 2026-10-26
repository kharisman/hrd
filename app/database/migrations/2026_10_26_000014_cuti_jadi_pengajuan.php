<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cuti & Izin menjadi salah satu jenis pengajuan: ikut alur Atasan → HRD, nomor surat & QR.
// Jenis cuti & tanggal disimpan sebagai kolom (bukan di json data) supaya rekap jatah cukup SQL biasa.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_pengajuan', fn (Blueprint $t) => $t->string('kode', 20)->nullable()->unique()->after('id'));
        Schema::table('pengajuan', function (Blueprint $t) {
            $t->foreignId('jenis_cuti_id')->nullable()->after('jenis_pengajuan_id')->constrained('jenis_cuti');
            $t->date('tgl_mulai')->nullable()->after('jenis_cuti_id');
            $t->date('tgl_selesai')->nullable()->after('tgl_mulai');
        });

        DB::table('jenis_pengajuan')->increment('urutan');
        DB::table('jenis_pengajuan')->insert([
            'kode' => 'cuti', 'nama' => 'Cuti & Izin', 'kategori' => 'Kehadiran & Waktu Kerja',
            'keterangan' => 'Cuti tahunan, sakit, melahirkan, alasan penting, atau izin. Jatah dihitung per tahun kalender.',
            'isian' => json_encode([
                ['key' => 'alasan', 'label' => 'Alasan', 'tipe' => 'textarea', 'wajib' => true, 'opsi' => []],
                ['key' => 'lampiran', 'label' => 'Lampiran (mis. surat dokter)', 'tipe' => 'file', 'wajib' => false, 'opsi' => []],
            ]),
            'alur' => 'atasan_hrd', 'boleh_anonim' => false, 'aktif' => true, 'urutan' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Modul cuti lama belum berisi data
        Schema::dropIfExists('cuti');
    }
};
