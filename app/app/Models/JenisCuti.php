<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisCuti extends Model
{
    protected $connection = 'mysql';
    protected $table = 'jenis_cuti';
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean'];

    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class);
    }

    /**
     * Rekap jatah per jenis untuk satu pegawai (boleh beberapa akun staff ber-NIP sama) di satu tahun kalender.
     * Terpakai = hari pengajuan cuti Disetujui + masih menunggu yang mulai di tahun itu. Tidak ada saldo tersimpan,
     * jadi jatah otomatis "reset" tiap 1 Januari dan sisa tahun lalu tidak terbawa.
     * ponytail: cuti yang melewati pergantian tahun dihitung penuh di tahun mulainya
     *
     * @return \Illuminate\Support\Collection<int, object{jenis: self, kuota: ?int, terpakai: int, sisa: ?int}>
     */
    public static function rekap(int|array $staffId, ?int $tahun = null)
    {
        $terpakai = Pengajuan::cuti()->whereIn('staff_id', (array) $staffId)
            ->whereIn('status', ['Disetujui', ...Pengajuan::MENUNGGU])
            ->whereYear('tgl_mulai', $tahun ?? now()->year)
            ->selectRaw('jenis_cuti_id, SUM(DATEDIFF(tgl_selesai, tgl_mulai) + 1) hari')
            ->groupBy('jenis_cuti_id')
            ->toBase() // tanpa model: kolom "hari" bentrok dengan accessor Pengajuan::getHariAttribute
            ->pluck('hari', 'jenis_cuti_id');

        return self::where('aktif', true)->orderBy('id')->get()->map(fn ($j) => (object) [
            'jenis' => $j,
            'kuota' => $j->kuota,
            'terpakai' => (int) ($terpakai[$j->id] ?? 0),
            'sisa' => $j->kuota === null ? null : max(0, $j->kuota - (int) ($terpakai[$j->id] ?? 0)),
        ])->keyBy(fn ($r) => $r->jenis->id);
    }
}
