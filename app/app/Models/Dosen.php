<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    protected $connection = 'mysql';
    protected $table = 'dosens';
    protected $guarded = ['id'];
    protected $casts = ['tmt_jabfung' => 'date', 'tgl_lahir' => 'date', 'mulai_kerja' => 'date', 'serdos' => 'boolean', 'aktif' => 'boolean'];

    const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
    const GOL_DAR = ['A', 'B', 'AB', 'O'];

    const JENJANG = ['Tenaga Pengajar', 'Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Profesor'];
    const STATUS = ['Tetap', 'Tidak Tetap', 'Luar Biasa'];
    const PRODI = ['Sistem Informasi', 'Informatika', 'Bisnis Digital'];

    // Data pegawai (staff), null untuk dosen non-staff
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
