<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPendidikan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'riwayat_pendidikan';
    protected $guarded = ['id'];

    // Urutan = tingkat (untuk menentukan pendidikan terakhir)
    const JENJANG = ['SMA', 'D3', 'S1', 'Profesi', 'S2', 'S3'];
    const STATUS = ['Lulus', 'Sedang Studi Lanjut', 'Tidak Selesai'];
    const PEMBIAYAAN = ['Mandiri', 'Beasiswa Institusi', 'Beasiswa Luar'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function getNamaAttribute(): string
    {
        return $this->staff->nama ?? $this->dosen->nama ?? '-';
    }

    // Riwayat milik satu orang: dosen yang juga pegawai punya satu riwayat gabungan
    public static function milik(?Staff $staff, ?Dosen $dosen)
    {
        $staffId = $staff?->id ?? $dosen?->staff_id;
        $dosenId = $dosen?->id ?? $staff?->dosen?->id;
        return self::where(fn ($q) => $q
            ->when($staffId, fn ($w) => $w->orWhere('staff_id', $staffId))
            ->when($dosenId, fn ($w) => $w->orWhere('dosen_id', $dosenId))
            ->when(!$staffId && !$dosenId, fn ($w) => $w->whereRaw('1 = 0')))
            ->orderByDesc('tahun_lulus')->orderByDesc('tahun_masuk');
    }

    // Jenjang tertinggi yang sudah lulus → dipakai sebagai "pendidikan terakhir"
    public static function tertinggi($riwayat): ?string
    {
        return $riwayat->where('status', 'Lulus')
            ->sortByDesc(fn ($r) => array_search($r->jenjang, self::JENJANG))
            ->first()?->jenjang;
    }

    // Perbarui pendidikan_terakhir pegawai & dosen pemilik riwayat
    public static function perbaruiPendidikanTerakhir(?int $staffId, ?int $dosenId): void
    {
        $staff = $staffId ? Staff::find($staffId) : null;
        $dosen = $dosenId ? Dosen::find($dosenId) : null;
        $tertinggi = self::tertinggi(self::milik($staff, $dosen)->get());

        $staff?->update(['pendidikan_terakhir' => $tertinggi]);
        // Pilihan pendidikan dosen hanya S1/S2/S3; selain itu isian manual dibiarkan
        $dosen?->update(['pendidikan_terakhir' => in_array($tertinggi, ['S1', 'S2', 'S3']) ? $tertinggi : $dosen->pendidikan_terakhir]);
    }
}
