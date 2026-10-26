<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPengajuan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'jenis_pengajuan';
    protected $guarded = ['id'];
    protected $casts = ['isian' => 'array', 'aktif' => 'boolean', 'boleh_anonim' => 'boolean'];

    const ALUR = ['atasan_hrd' => 'Atasan → HRD', 'atasan' => 'Atasan saja', 'hrd' => 'HRD saja'];
    const TIPE = ['text' => 'Teks singkat', 'textarea' => 'Teks panjang', 'date' => 'Tanggal', 'time' => 'Jam',
        'number' => 'Angka', 'rupiah' => 'Rupiah', 'select' => 'Pilihan', 'file' => 'Berkas'];
    const KATEGORI = ['Kehadiran & Waktu Kerja', 'Data & Dokumen', 'Pengembangan Karier', 'Keuangan & Kesejahteraan', 'Hubungan Kerja', 'Lainnya'];

    public function isCuti(): bool
    {
        return $this->kode === 'cuti';
    }

    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class);
    }
}
