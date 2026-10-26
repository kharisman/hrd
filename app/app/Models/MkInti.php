<?php

namespace App\Models;

use App\Models\Concerns\TautProdi;
use Illuminate\Database\Eloquent\Model;

// Mata kuliah inti prodi per kurikulum + dosen yang menguasainya
class MkInti extends Model
{
    use TautProdi;

    protected $connection = 'mysql';
    protected $table = 'mk_inti';
    protected $guarded = ['id'];

    public function dosen()
    {
        return $this->belongsToMany(Dosen::class, 'mk_dosen');
    }

    // Dosen yang menguasai = dicentang HRD ∪ pernah mengampu di eUniversity (diisi DashboardDosenController::hitungKuasai)
    public array $kuasai = [];

    // Selisih jumlah dosen yang menguasai dengan kebutuhan (negatif = kurang)
    public function getGapAttribute(): int
    {
        return count($this->kuasai ?: $this->dosen->pluck('id')->all()) - $this->kebutuhan;
    }
}
