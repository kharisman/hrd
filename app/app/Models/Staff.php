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
    protected $casts = ['tgl_lahir' => 'date', 'mulai_kerja' => 'date', 'tanggal_keluar' => 'date', 'kontrak_berakhir' => 'date', 'aktif' => 'boolean', 'roles' => 'array'];

    // Pegawai aktif = akun login eUniversity-nya aktif (diisi sinkron)
    /**
     * Akun staff utama satu orang (NIP sama bisa punya beberapa akun): aktif dulu, lalu id terkecil.
     * Data per orang (DISC, kehadiran) disimpan di akun ini.
     */
    public static function utama(?string $nip): ?self
    {
        return $nip ? static::where('nip', $nip)->orderByDesc('aktif')->orderBy('id')->first() : null;
    }

    // Satu baris per orang: akun utama tiap NIP
    public static function perOrang($query)
    {
        return $query->whereNotNull('nip')->orderByDesc('aktif')->orderBy('id')->get()->unique('nip')->sortBy('nama')->values();
    }

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
