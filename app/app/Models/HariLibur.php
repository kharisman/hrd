<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $connection = 'mysql';
    protected $table = 'hari_libur';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'cuti_bersama' => 'boolean'];

    // Jumlah hari cuti bersama (hari kerja) di satu tahun → memotong jatah cuti tahunan
    public static function cutiBersama(int $tahun): int
    {
        return self::where('cuti_bersama', true)->whereYear('tanggal', $tahun)->get()->reject(fn ($h) => $h->tanggal->isWeekend())->count();
    }

    // Hari kerja di antara dua tanggal (Senin–Jumat, minus libur nasional & cuti bersama); $dari tidak ikut dihitung
    public static function hariKerja(Carbon $dari, Carbon $sampai): int
    {
        if ($sampai->lte($dari)) {
            return 0;
        }
        $libur = self::whereBetween('tanggal', [$dari->copy()->addDay()->toDateString(), $sampai->toDateString()])->pluck('tanggal')->map->toDateString();
        return collect(\Carbon\CarbonPeriod::create($dari->copy()->addDay(), $sampai))
            ->reject(fn ($t) => $t->isWeekend() || $libur->contains($t->toDateString()))->count();
    }
}
