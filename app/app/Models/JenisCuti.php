<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisCuti extends Model
{
    protected $connection = 'mysql';
    protected $table = 'jenis_cuti';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];

    public function cuti()
    {
        return $this->hasMany(Cuti::class);
    }

    /**
     * Rekap jatah per jenis untuk satu pegawai di satu tahun kalender.
     * Terpakai = hari cuti Disetujui + Menunggu yang mulai di tahun itu. Tidak ada saldo tersimpan,
     * jadi jatah otomatis "reset" tiap 1 Januari dan sisa tahun lalu tidak terbawa.
     * ponytail: cuti yang melewati pergantian tahun dihitung penuh di tahun mulainya
     *
     * @return \Illuminate\Support\Collection<int, object{jenis: self, kuota: ?int, terpakai: int, sisa: ?int}>
     */
    public static function rekap(int $staffId, ?int $tahun = null, ?int $kecualiCutiId = null)
    {
        $terpakai = Cuti::where('staff_id', $staffId)
            ->whereIn('status', ['Disetujui', 'Menunggu'])
            ->whereYear('tgl_mulai', $tahun ?? now()->year)
            ->when($kecualiCutiId, fn ($q) => $q->where('id', '!=', $kecualiCutiId))
            ->selectRaw('jenis_cuti_id, SUM(DATEDIFF(tgl_selesai, tgl_mulai) + 1) hari')
            ->groupBy('jenis_cuti_id')
            ->toBase() // tanpa model: kolom "hari" bentrok dengan accessor Cuti::getHariAttribute
            ->pluck('hari', 'jenis_cuti_id');

        return self::where('aktif', true)->orderBy('id')->get()->map(fn ($j) => (object) [
            'jenis' => $j,
            'kuota' => $j->kuota,
            'terpakai' => (int) ($terpakai[$j->id] ?? 0),
            'sisa' => $j->kuota === null ? null : max(0, $j->kuota - (int) ($terpakai[$j->id] ?? 0)),
        ])->keyBy(fn ($r) => $r->jenis->id);
    }
}
