<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Kelas yang diampu dosen per periode (API /krs/{id}/grup). Satu baris = satu kelas (grup).
class Mengajar extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mengajar';
    protected $guarded = ['id'];
}
