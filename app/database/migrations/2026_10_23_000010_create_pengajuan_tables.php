<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Modul pengajuan umum: jenis form bisa dikonfigurasi (isian + alur persetujuan), tanpa ubah kode.
return new class extends Migration
{
    public function up()
    {
        Schema::create('jenis_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('kategori');
            $table->string('keterangan')->nullable();
            // [{key, label, tipe: text|textarea|date|time|number|rupiah|select|file, wajib: bool, opsi: [..]}]
            $table->json('isian');
            $table->string('alur', 20)->default('atasan_hrd'); // atasan_hrd | atasan | hrd
            $table->boolean('boleh_anonim')->default(false);
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_pengajuan_id')->constrained('jenis_pengajuan');
            $table->unsignedBigInteger('staff_id')->index(); // pengaju (tetap disimpan walau anonim, tapi disembunyikan di tampilan)
            $table->boolean('anonim')->default(false);
            $table->json('data');                  // jawaban isian (berkas = path di storage privat)
            $table->string('status', 30)->index(); // Menunggu Atasan | Menunggu HRD | Disetujui | Ditolak | Dibatalkan
            $table->string('atasan_oleh')->nullable();
            $table->timestamp('atasan_at')->nullable();
            $table->text('atasan_catatan')->nullable();
            $table->string('hrd_oleh')->nullable();
            $table->timestamp('hrd_at')->nullable();
            $table->text('hrd_catatan')->nullable();
            $table->timestamps();
        });

        $f = fn ($key, $label, $tipe = 'text', $wajib = true, $opsi = []) => compact('key', 'label', 'tipe', 'wajib', 'opsi');
        $jenis = [
            // Kehadiran & Waktu Kerja
            ['Dinas Luar / Tugas Luar', 'Kehadiran & Waktu Kerja', 'Penugasan di luar kampus', 'atasan_hrd', [
                $f('tujuan', 'Tujuan / Lokasi'), $f('keperluan', 'Keperluan', 'textarea'),
                $f('mulai', 'Tanggal Berangkat', 'date'), $f('selesai', 'Tanggal Kembali', 'date'),
                $f('biaya', 'Perkiraan Biaya', 'rupiah', false), $f('surat_tugas', 'Surat Tugas / Undangan', 'file', false)]],
            ['Koreksi Absensi', 'Kehadiran & Waktu Kerja', 'Lupa absen atau mesin absen bermasalah', 'atasan_hrd', [
                $f('tanggal', 'Tanggal', 'date'), $f('jenis', 'Yang Dikoreksi', 'select', true, ['Absen Masuk', 'Absen Pulang', 'Masuk & Pulang']),
                $f('jam_masuk', 'Jam Masuk Sebenarnya', 'time', false), $f('jam_pulang', 'Jam Pulang Sebenarnya', 'time', false),
                $f('alasan', 'Alasan', 'textarea')]],
            ['Tukar Jadwal / Shift', 'Kehadiran & Waktu Kerja', 'Untuk staf yang bekerja dengan jadwal/shift', 'atasan', [
                $f('tanggal', 'Tanggal Jadwal', 'date'), $f('jadwal_asal', 'Jadwal Semula'), $f('jadwal_baru', 'Jadwal Pengganti'),
                $f('pengganti', 'Rekan Pengganti (bila ada)', 'text', false), $f('alasan', 'Alasan', 'textarea')]],
            // Data & Dokumen
            ['Perubahan Data Pribadi', 'Data & Dokumen', 'Alamat, kontak, rekening, status keluarga', 'hrd', [
                $f('data', 'Data yang Diubah', 'select', true, ['Alamat', 'No. HP', 'Rekening Bank', 'Status Pernikahan', 'Jumlah Tanggungan', 'Lainnya']),
                $f('lama', 'Data Lama', 'text', false), $f('baru', 'Data Baru', 'textarea'), $f('bukti', 'Dokumen Pendukung (KK, buku tabungan, dll.)', 'file', false)]],
            ['Surat Keterangan', 'Data & Dokumen', 'Permintaan surat dari HRD', 'hrd', [
                $f('jenis_surat', 'Jenis Surat', 'select', true, ['Keterangan Bekerja', 'Keterangan Aktif Mengajar', 'Keterangan Penghasilan', 'Rekomendasi', 'Lainnya']),
                $f('keperluan', 'Keperluan', 'textarea'), $f('tujuan', 'Ditujukan Kepada', 'text', false), $f('jumlah', 'Jumlah Rangkap', 'number', false)]],
            ['Pembaruan Riwayat Pendidikan', 'Data & Dokumen', 'Lapor ijazah / pendidikan baru untuk diverifikasi HRD', 'hrd', [
                $f('jenjang', 'Jenjang', 'select', true, ['SMA', 'D3', 'S1', 'Profesi', 'S2', 'S3']), $f('institusi', 'Institusi'),
                $f('program_studi', 'Program Studi', 'text', false), $f('tahun_lulus', 'Tahun Lulus', 'number'), $f('ijazah', 'Ijazah / Transkrip', 'file')]],
            // Pengembangan Karier
            ['Izin / Tugas Belajar', 'Pengembangan Karier', 'Studi lanjut dengan izin atau tugas dari institusi', 'atasan_hrd', [
                $f('status', 'Status', 'select', true, ['Izin Belajar', 'Tugas Belajar']), $f('jenjang', 'Jenjang', 'select', true, ['S1', 'Profesi', 'S2', 'S3']),
                $f('institusi', 'Perguruan Tinggi'), $f('program_studi', 'Program Studi'), $f('mulai', 'Mulai Kuliah', 'date'),
                $f('pembiayaan', 'Pembiayaan', 'select', true, ['Mandiri', 'Beasiswa Institusi', 'Beasiswa Luar']), $f('surat_diterima', 'Surat Diterima (LoA)', 'file', false)]],
            ['Pelatihan / Seminar / Sertifikasi', 'Pengembangan Karier', 'Pengajuan mengikuti kegiatan pengembangan diri', 'atasan_hrd', [
                $f('kegiatan', 'Nama Kegiatan'), $f('penyelenggara', 'Penyelenggara'), $f('mulai', 'Tanggal Mulai', 'date'),
                $f('selesai', 'Tanggal Selesai', 'date'), $f('biaya', 'Biaya', 'rupiah', false), $f('manfaat', 'Manfaat bagi Unit', 'textarea'),
                $f('brosur', 'Brosur / Undangan', 'file', false)]],
            ['Usulan Kenaikan Jabatan Fungsional', 'Pengembangan Karier', 'Khusus dosen: AA, Lektor, Lektor Kepala, Profesor', 'atasan_hrd', [
                $f('jabatan_sekarang', 'Jabatan Sekarang', 'select', true, ['Tenaga Pengajar', 'Asisten Ahli', 'Lektor', 'Lektor Kepala']),
                $f('jabatan_usulan', 'Jabatan yang Diusulkan', 'select', true, ['Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Profesor']),
                $f('angka_kredit', 'Angka Kredit', 'number'), $f('berkas', 'Berkas Usulan (PAK, dll.)', 'file')]],
            ['Pengajuan Sertifikasi Dosen', 'Pengembangan Karier', 'Khusus dosen', 'atasan_hrd', [
                $f('periode', 'Periode / Tahun Serdos'), $f('jabatan', 'Jabatan Fungsional Saat Ini'),
                $f('catatan', 'Catatan', 'textarea', false), $f('berkas', 'Berkas Pendukung', 'file', false)]],
            // Keuangan & Kesejahteraan
            ['Reimburse', 'Keuangan & Kesejahteraan', 'Penggantian biaya yang sudah dikeluarkan', 'atasan_hrd', [
                $f('jenis', 'Jenis', 'select', true, ['Kesehatan', 'Transport', 'Perjalanan Dinas', 'Lainnya']), $f('tanggal', 'Tanggal Transaksi', 'date'),
                $f('nominal', 'Nominal', 'rupiah'), $f('keterangan', 'Keterangan', 'textarea'), $f('bukti', 'Bukti / Kuitansi', 'file')]],
            ['Kasbon / Pinjaman Pegawai', 'Keuangan & Kesejahteraan', 'Pinjaman dipotong dari gaji', 'atasan_hrd', [
                $f('nominal', 'Nominal', 'rupiah'), $f('tenor', 'Lama Cicilan (bulan)', 'number'), $f('keperluan', 'Keperluan', 'textarea')]],
            ['Klaim Tunjangan', 'Keuangan & Kesejahteraan', 'Tunjangan duka, pernikahan, kelahiran', 'hrd', [
                $f('jenis', 'Jenis Tunjangan', 'select', true, ['Duka Cita', 'Pernikahan', 'Kelahiran Anak', 'Lainnya']),
                $f('tanggal', 'Tanggal Peristiwa', 'date'), $f('keterangan', 'Keterangan', 'textarea', false), $f('bukti', 'Bukti (akta, surat, dll.)', 'file')]],
            // Hubungan Kerja
            ['Pengunduran Diri', 'Hubungan Kerja', 'Resign / berhenti bekerja', 'atasan_hrd', [
                $f('tanggal_efektif', 'Tanggal Efektif', 'date'), $f('alasan', 'Alasan', 'textarea'), $f('surat', 'Surat Pengunduran Diri', 'file', false)]],
            ['Pengaduan / Keluhan', 'Hubungan Kerja', 'Bisa dikirim anonim, langsung ke HRD', 'hrd', [
                $f('kategori', 'Kategori', 'select', true, ['Lingkungan Kerja', 'Perilaku Rekan/Atasan', 'Fasilitas', 'Kebijakan', 'Lainnya']),
                $f('uraian', 'Uraian', 'textarea'), $f('bukti', 'Bukti (bila ada)', 'file', false)]],
            ['Mutasi / Pindah Unit', 'Hubungan Kerja', 'Permohonan pindah divisi/unit', 'atasan_hrd', [
                $f('unit_tujuan', 'Unit / Divisi Tujuan'), $f('alasan', 'Alasan', 'textarea'), $f('mulai', 'Diharapkan Mulai', 'date', false)]],
        ];

        foreach ($jenis as $i => [$nama, $kategori, $ket, $alur, $isian]) {
            DB::table('jenis_pengajuan')->insert([
                'nama' => $nama, 'kategori' => $kategori, 'keterangan' => $ket, 'alur' => $alur,
                'isian' => json_encode($isian), 'boleh_anonim' => $nama === 'Pengaduan / Keluhan',
                'aktif' => true, 'urutan' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('pengajuan');
        Schema::dropIfExists('jenis_pengajuan');
    }
};
