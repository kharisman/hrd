<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Master struktur organisasi (pasangan divisi × level) dari API /struktur
class Struktur extends Model
{
    protected $connection = 'mysql';
    protected $table = 'struktur';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];
}
