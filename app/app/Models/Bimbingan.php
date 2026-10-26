<?php

namespace App\Models;

use App\Models\Concerns\TautProdi;
use Illuminate\Database\Eloquent\Model;

// Bimbingan PKL / proposal / skripsi per periode (API /pkl-skripsi), satu baris per mahasiswa yang dibimbing
class Bimbingan extends Model
{
    use TautProdi;

    protected $connection = 'mysql';
    protected $table = 'bimbingan';
    protected $guarded = ['id'];

    const JENIS = ['pkl' => 'PKL', 'proposal' => 'Proposal', 'skripsi' => 'Skripsi'];
}
