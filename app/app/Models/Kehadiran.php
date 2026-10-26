<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Rekap bulanan per NIP: total menit terlambat & hari tidak hadir
class Kehadiran extends Model
{
    protected $connection = 'mysql';
    protected $table = 'kehadiran';
    protected $guarded = ['id'];
    protected $casts = ['tidak_hadir_hari' => 'float'];

    const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
}
