<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Jumlah mahasiswa per prodi per tahun ajaran → rasio dosen : mahasiswa (target maks. mahasiswa per dosen)
class RasioProdi extends Model
{
    protected $connection = 'mysql';
    protected $table = 'rasio_prodi';
    protected $guarded = ['id'];
}
