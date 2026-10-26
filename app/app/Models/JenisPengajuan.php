<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPengajuan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'jenis_pengajuan';
    protected $guarded = ['id'];
    protected $casts = ['isian' => 'array', 'tahapan' => 'array', 'aktif' => 'boolean', 'boleh_anonim' => 'boolean'];

    // Tahap persetujuan yang bisa disusun bebas: atasan langsung, admin HRD, atau pemegang peran pejabat
    const TAHAP = ['atasan' => 'Atasan', 'hrd' => 'HRD'] + Pejabat::PERAN;
    const TIPE = ['text' => 'Teks singkat', 'textarea' => 'Teks panjang', 'date' => 'Tanggal', 'time' => 'Jam',
        'number' => 'Angka', 'rupiah' => 'Rupiah', 'select' => 'Pilihan', 'file' => 'Berkas'];
    const KATEGORI = ['Kehadiran & Waktu Kerja', 'Data & Dokumen', 'Pengembangan Karier', 'Keuangan & Kesejahteraan', 'Hubungan Kerja', 'Lainnya'];

    public static function teksAlur(array $tahapan): string
    {
        return implode(' → ', array_map(fn ($t) => self::TAHAP[$t] ?? $t, $tahapan));
    }

    public function isCuti(): bool
    {
        return $this->kode === 'cuti';
    }

    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class);
    }
}
