<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pegawai = staff eUniversity. Kolom identitas/biodata dari API (sinkron);
// atasan_id, status_kepegawaian = isian HRD, tidak ditimpa sinkron.
// pendidikan_terakhir = otomatis dari riwayat pendidikan (jenjang tertinggi yang lulus).
class Staff extends Model
{
    protected $connection = 'mysql';
    protected $table = 'staffs';
    protected $guarded = ['id'];
    protected $casts = ['tgl_lahir' => 'date', 'mulai_kerja' => 'date', 'tanggal_keluar' => 'date', 'kontrak_berakhir' => 'date', 'aktif' => 'boolean'];

    // Pegawai aktif = akun login eUniversity-nya aktif (diisi sinkron)
    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }

    const STATUS = ['Tetap', 'Kontrak', 'Honorer'];

    public function atasan()
    {
        return $this->belongsTo(Staff::class, 'atasan_id');
    }

    public function bawahan()
    {
        return $this->hasMany(Staff::class, 'atasan_id');
    }

    public function dosen()
    {
        return $this->hasOne(Dosen::class, 'staff_id');
    }
}
