<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pengaturan key-value (hrd_pengaturan): penandatangan dokumen, kota, kode surat
class Pengaturan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function nilai(string $kunci, ?string $bawaan = null): ?string
    {
        return static::whereKey($kunci)->value('nilai') ?? $bawaan;
    }

    // Pejabat penandatangan dokumen: pegawai yang dipilih di Pengaturan, atau otomatis pegawai aktif berjabatan Rektor
    public static function penandatangan(): ?Staff
    {
        return Staff::find(static::nilai('ttd_staff_id')) ?? Staff::aktif()->where('level', 'Rektor')->orderBy('id')->first();
    }

    public static function simpan(array $data): void
    {
        foreach ($data as $kunci => $nilai) {
            static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }
    }
}
