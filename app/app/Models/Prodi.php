<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Program studi dari API eUniversity (/prodi), disinkron hrd:sync
class Prodi extends Model
{
    protected $connection = 'mysql';
    protected $table = 'prodi';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];

    // Nama di data HRD → prodi: "D3 Akuntansi" = Akuntansi jenjang D3; nama lain = prodi sarjana (S1) dulu
    public static function cari(?string $nama): ?self
    {
        if (!$nama = trim((string) $nama)) {
            return null;
        }
        return str_starts_with($nama, 'D3 ')
            ? static::where(['nama' => substr($nama, 3), 'jenjang' => 'Diploma Tiga'])->first()
            : static::where('nama', $nama)->orderByRaw("jenjang = 'Strata Satu' desc")->first();
    }
}
