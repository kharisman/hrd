<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Periode kuliah dari API /krs (ganjil, genap, pendek)
class PeriodeKrs extends Model
{
    protected $connection = 'mysql';
    protected $table = 'periode_krs';
    protected $guarded = ['id'];
    protected $casts = ['mulai' => 'date', 'selesai' => 'date'];

    // Tahun ajaran dari periode reguler, terbaru dulu
    public static function tahunAjaran()
    {
        return static::whereIn('tipe', ['ganjil', 'genap'])->orderByDesc('api_id')->pluck('tahun_akademik')->unique()->values();
    }
}
