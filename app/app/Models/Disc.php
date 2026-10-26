<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Peta karakter (DISC) per orang (staff_id = akun staff utama orang itu, lihat Staff::utama). Komitmen = rata-rata inisiatif, tanggung jawab, totalitas, ekstra;
// kompetensi = rata-rata pengalaman, sertifikasi, dukungan, proyek (skala 1–4).
// Kategori & cara memimpin mengikuti spreadsheet lama (kepemimpinan situasional).
class Disc extends Model
{
    protected $connection = 'mysql';
    protected $table = 'disc';
    protected $guarded = ['id'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
    protected $casts = ['dinilai_at' => 'date'];

    const KOMITMEN = ['inisiatif' => 'Inisiatif', 'tanggung_jawab' => 'Responsibility', 'totalitas' => 'Totalitas', 'ekstra' => 'Extra'];
    const KOMPETENSI = ['pengalaman' => 'Pengalaman', 'sertifikasi' => 'Sertifikasi', 'dukungan' => 'Support', 'proyek' => 'Project'];
    const GAYA = ['Tinggi' => 'Delegating', 'Menengah' => 'Supporting', 'Cukup' => 'Coaching', 'Rendah' => 'Directing'];

    public function skor(array $kolom): ?float
    {
        $v = collect(array_keys($kolom))->map(fn ($k) => $this->$k)->filter(fn ($x) => $x !== null);
        return $v->isEmpty() ? null : round($v->avg(), 2);
    }

    public function getKomitmenAttribute(): ?float
    {
        return $this->skor(self::KOMITMEN);
    }

    public function getKompetensiAttribute(): ?float
    {
        return $this->skor(self::KOMPETENSI);
    }

    // Ambang dicocokkan dengan hasil spreadsheet: 3,6 Tinggi; 2,75–3,5 Menengah; 1,75–2,5 Cukup; ≤1,5 Rendah
    public static function kategori(?float $v): ?string
    {
        return $v === null ? null : match (true) { $v > 3.5 => 'Tinggi', $v >= 2.75 => 'Menengah', $v >= 1.75 => 'Cukup', default => 'Rendah' };
    }
}
