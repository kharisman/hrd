<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Angkatan (kohort) mahasiswa per prodi dari API /angkatan, beserta kurikulum yang dipakainya
class Angkatan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'angkatan';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];
}
