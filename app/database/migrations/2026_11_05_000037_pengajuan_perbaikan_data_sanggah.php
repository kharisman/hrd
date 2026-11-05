<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Dokumen alur: dosen mengajukan perubahan data profil (validasi Kaprodi → HRD), pegawai menyanggah KPI/rapor (Atasan → HRD).
// Setelah disetujui, HRD memperbarui datanya di menu terkait (Data Dosen / Studi Lanjut / Input Data).
return new class extends Migration
{
    private function f(string $key, string $label, string $tipe, bool $wajib = false, array $opsi = []): array
    {
        return compact('key', 'label', 'tipe', 'wajib', 'opsi');
    }

    public function up(): void
    {
        $urut = (int) DB::table('jenis_pengajuan')->max('urutan');
        $baru = [
            [
                'kode' => 'perbaikan_profil', 'nama' => 'Perbaikan Data Profil Dosen', 'kategori' => 'Data & Dokumen',
                'keterangan' => 'Ajukan perubahan biodata, pendidikan, JJA/Serdos, kompetensi, atau roadmap karier. Divalidasi Kaprodi, disetujui HRD, lalu HRD memperbarui data.',
                'isian' => [
                    $this->f('bagian', 'Bagian data', 'select', true, ['Biodata', 'Pendidikan (S1–S3)', 'JJA / Serdos', 'Kompetensi / Sertifikasi', 'Roadmap Karier', 'Lainnya']),
                    $this->f('sekarang', 'Data saat ini', 'textarea'),
                    $this->f('seharusnya', 'Data yang benar / baru', 'textarea', true),
                    $this->f('bukti', 'Bukti (ijazah, SK, sertifikat)', 'file'),
                ],
                'tahapan' => ['kaprodi', 'hrd'],
            ],
            [
                'kode' => 'sanggah_kpi', 'nama' => 'Sanggah KPI / Rapor', 'kategori' => 'Pengembangan Karier',
                'keterangan' => 'Ajukan klarifikasi bila nilai KPI, rapor, kehadiran, atau keterlambatan tidak sesuai. Atasan meninjau, HRD menetapkan.',
                'isian' => [
                    $this->f('periode', 'Periode (mis. Oktober 2026 / Semester Ganjil 2026)', 'text', true),
                    $this->f('indikator', 'Indikator yang disanggah', 'select', true, ['KPI', 'Rapor', 'Kehadiran', 'Keterlambatan', 'Lainnya']),
                    $this->f('nilai', 'Nilai tercatat', 'text'),
                    $this->f('alasan', 'Alasan & nilai yang seharusnya', 'textarea', true),
                    $this->f('bukti', 'Bukti pendukung', 'file'),
                ],
                'tahapan' => ['atasan', 'hrd'],
            ],
        ];
        foreach ($baru as $j) {
            if (!DB::table('jenis_pengajuan')->where('kode', $j['kode'])->exists()) {
                DB::table('jenis_pengajuan')->insert(['isian' => json_encode($j['isian']), 'tahapan' => json_encode($j['tahapan']),
                    'boleh_anonim' => false, 'aktif' => true, 'urutan' => ++$urut, 'created_at' => now(), 'updated_at' => now()] + $j);
            }
        }
    }

    public function down(): void
    {
        DB::table('jenis_pengajuan')->whereIn('kode', ['perbaikan_profil', 'sanggah_kpi'])->delete();
    }
};
