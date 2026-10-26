<?php

namespace App\Models;

use App\Models\Concerns\TautProdi;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use TautProdi;

    protected $connection = 'mysql';
    protected $table = 'dosens';
    protected $guarded = ['id'];
    protected $casts = ['tmt_jabfung' => 'date', 'tmt_dosen' => 'date', 'tgl_lahir' => 'date', 'mulai_kerja' => 'date', 'serdos' => 'boolean', 'aktif' => 'boolean'];

    const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
    const GOL_DAR = ['A', 'B', 'AB', 'O'];

    const JENJANG = ['Tenaga Pengajar', 'Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Profesor'];
    const STATUS = ['Tetap', 'Tidak Tetap', 'Luar Biasa'];
    // ponytail: cadangan bila tabel prodi belum disinkron
    const PRODI = ['Sistem Informasi', 'Informatika', 'Bisnis Digital'];

    // Prodi S1 aktif dari eUniversity (dosen tetap HRD ada di prodi sarjana); nama prodi D3 bisa kembar (Sistem Informasi)
    public static function daftarProdi(): array
    {
        static $daftar;
        return $daftar ??= Prodi::where(['aktif' => true, 'jenjang' => 'Strata Satu'])->orderBy('nama')->pluck('nama')->all() ?: self::PRODI;
    }

    // Data pegawai (staff), null untuk dosen non-staff
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
