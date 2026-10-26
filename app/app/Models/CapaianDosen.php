<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Isi rapor dosen: roadmap karier, sertifikat & training, prestasi, publikasi, rekognisi
class CapaianDosen extends Model
{
    protected $connection = 'mysql';
    protected $table = 'capaian_dosen';
    protected $guarded = ['id'];

    const JENIS = ['roadmap' => 'Roadmap Karier', 'sertifikat' => 'Sertifikasi & Training', 'prestasi' => 'Prestasi (HKI, Buku, Hibah)', 'publikasi' => 'Publikasi', 'rekognisi' => 'Rekognisi'];
    const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // Sertifikat kompetensi (BNSP/LSP) dibedakan dari training biasa
    public function getKompetensiAttribute(): bool
    {
        return $this->jenis === 'sertifikat' && (bool) preg_match('/BNSP|LSP|kompetensi/i', $this->judul . ' ' . $this->sumber);
    }
}
