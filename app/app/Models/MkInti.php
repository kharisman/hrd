<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Mata kuliah inti prodi per kurikulum + dosen yang menguasainya
class MkInti extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mk_inti';
    protected $guarded = ['id'];

    public function dosen()
    {
        return $this->belongsToMany(Dosen::class, 'mk_dosen');
    }

    // Selisih jumlah dosen yang menguasai dengan kebutuhan (negatif = kurang)
    public function getGapAttribute(): int
    {
        return $this->dosen->count() - $this->kebutuhan;
    }
}
