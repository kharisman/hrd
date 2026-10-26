<?php

namespace App\Models;

use App\Models\Concerns\TautProdi;
use Illuminate\Database\Eloquent\Model;

// Jumlah mahasiswa per prodi per tahun ajaran → rasio dosen : mahasiswa (target maks. mahasiswa per dosen)
class RasioProdi extends Model
{
    use TautProdi;

    protected $connection = 'mysql';
    protected $table = 'rasio_prodi';
    protected $guarded = ['id'];
    protected $casts = ['per_angkatan' => 'array'];
}
