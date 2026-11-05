<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Dosen melaporkan capaiannya sendiri lewat Pengajuan (atasan → HRD); disetujui → baris capaian_dosen (rapor & dashboard).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capaian_dosen', fn (Blueprint $t) => $t->foreignId('pengajuan_id')->nullable()->unique()->after('dosen_id')->constrained('pengajuan')->nullOnDelete());

        DB::table('jenis_pengajuan')->insert([
            'kode' => 'capaian', 'nama' => 'Laporan Capaian Dosen', 'kategori' => 'Pengembangan Karier',
            'keterangan' => 'Sertifikat/training, prestasi (HKI, buku, hibah), publikasi, atau rekognisi. Setelah disetujui otomatis masuk rapor dosen.',
            'isian' => json_encode([
                ['key' => 'jenis', 'label' => 'Jenis capaian', 'tipe' => 'select', 'wajib' => true, 'opsi' => ['Sertifikasi & Training', 'Prestasi (HKI, Buku, Hibah)', 'Publikasi', 'Rekognisi']],
                ['key' => 'judul', 'label' => 'Judul / nama kegiatan', 'tipe' => 'text', 'wajib' => true, 'opsi' => []],
                ['key' => 'sumber', 'label' => 'Penyelenggara / penerbit (mis. BNSP, LSP, nama jurnal)', 'tipe' => 'text', 'wajib' => false, 'opsi' => []],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'date', 'wajib' => true, 'opsi' => []],
                ['key' => 'status', 'label' => 'Peringkat (mis. Sinta 2, Scopus Q3)', 'tipe' => 'text', 'wajib' => false, 'opsi' => []],
                ['key' => 'link', 'label' => 'Tautan', 'tipe' => 'text', 'wajib' => false, 'opsi' => []],
                ['key' => 'bukti', 'label' => 'Bukti (sertifikat / LoA / SK)', 'tipe' => 'file', 'wajib' => true, 'opsi' => []],
            ]),
            'tahapan' => json_encode(['atasan', 'hrd']), 'boleh_anonim' => false, 'aktif' => true,
            'urutan' => (int) DB::table('jenis_pengajuan')->max('urutan') + 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('capaian_dosen', fn (Blueprint $t) => $t->dropConstrainedForeignId('pengajuan_id'));
        DB::table('jenis_pengajuan')->where('kode', 'capaian')->delete();
    }
};
