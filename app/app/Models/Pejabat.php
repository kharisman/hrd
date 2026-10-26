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

    // Peran yang dipegang orang ini (semua akun staff-nya)
    public static function peranDari($staffIds)
    {
        return static::whereIn('staff_id', $staffIds)->pluck('peran')->unique()->values();
    }
}
