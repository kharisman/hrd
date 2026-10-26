<?php

namespace App\Models;

use App\Models\Concerns\TautProdi;
use Illuminate\Database\Eloquent\Model;

// Jumlah mahasiswa aktif per prodi per tahun ajaran, salinan dari eUniversity (hrd:sync, tanpa isian manual)
// → rasio dosen : mahasiswa
class RasioProdi extends Model
{
    use TautProdi;

    const TARGET = 22; // maks. mahasiswa per dosen

    protected $connection = 'mysql';
    protected $table = 'rasio_prodi';
    protected $guarded = ['id'];
    protected $casts = ['per_angkatan' => 'array'];
}
