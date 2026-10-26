<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Skill dosen per tahun: level sekarang vs target (skala 1–5) & rencana aksi
class Kompetensi extends Model
{
    protected $connection = 'mysql';
    protected $table = 'kompetensi';
    protected $guarded = ['id'];

    const KELOMPOK = ['personal' => 'Kompetensi Personal', 'jabatan' => 'Kompetensi Jabatan'];
    // Daftar baku dari spreadsheet; skill lain boleh ditambah per dosen
    const BAKU = [
        'personal' => ['Leadership', 'Teamwork', 'Literasi komputer & internet', 'Analytical', 'Communication'],
        'jabatan' => ['Linearisasi dengan Iduka', 'Kompetensi akademik', 'Linearisasi dengan prodi'],
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }
}
