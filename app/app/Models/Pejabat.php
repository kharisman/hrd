<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Peran lokal HRD untuk tahap persetujuan (selain atasan langsung & admin HRD). Satu peran bisa dipegang beberapa pegawai.
class Pejabat extends Model
{
    protected $connection = 'mysql';
    protected $table = 'pejabat';
    protected $guarded = ['id'];

    const PERAN = ['warek1' => 'Warek I', 'warek2' => 'Warek II', 'keuangan' => 'Keuangan', 'baak' => 'BAAK'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    // Peran yang bisa diambil dari role eUniversity (nama role = kunci peran); Keuangan belum punya role
    const DARI_ROLE = ['warek1', 'warek2', 'baak'];

    // Peran orang ini: penunjukan di HRD + role eUniversity pegawainya (warek1, warek2, baak)
    public static function peranDari($staffIds)
    {
        $roles = Staff::whereIn('id', $staffIds)->where('aktif', true)->pluck('roles')->flatten()->filter();
        return static::whereIn('staff_id', $staffIds)->pluck('peran')
            ->merge($roles->intersect(self::DARI_ROLE))->unique()->values();
    }

    // Pemegang peran dari role eUniversity (untuk ditampilkan di Pengaturan → Peran Pejabat)
    public static function dariRole(string $peran)
    {
        return in_array($peran, self::DARI_ROLE) ? Staff::where('aktif', true)->whereJsonContains('roles', $peran)->orderBy('nama')->get() : collect();
    }
}
