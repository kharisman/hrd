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
     * Terpakai = hari cuti disetujui + masih dalam proses yang mulai di tahun itu. Tidak ada saldo tersimpan,
     * jadi jatah otomatis "reset" tiap 1 Januari dan sisa tahun lalu tidak terbawa.
     * ponytail: cuti yang melewati pergantian tahun dihitung penuh di tahun mulainya
     *
     * @return \Illuminate\Support\Collection<int, object{jenis: self, kuota: ?int, terpakai: int, sisa: ?int}>
     */
    public static function rekap(int|array $staffId, ?int $tahun = null)
    {
        // Hari cuti per jenis: disetujui & masih dalam proses persetujuan (keduanya mengurangi jatah)
        $hari = fn ($q) => $q->whereIn('staff_id', (array) $staffId)->whereYear('tgl_mulai', $tahun ?? now()->year)
            ->selectRaw('jenis_cuti_id, SUM(DATEDIFF(tgl_selesai, tgl_mulai) + 1) hari')->groupBy('jenis_cuti_id')
            ->toBase() // tanpa model: kolom "hari" bentrok dengan accessor Pengajuan::getHariAttribute
            ->pluck('hari', 'jenis_cuti_id');
        $disetujui = $hari(Pengajuan::cuti()->where('status', 'Disetujui'));
        $proses = $hari(Pengajuan::cuti()->menunggu());

        return self::where('aktif', true)->orderBy('id')->get()->map(function ($j) use ($disetujui, $proses) {
            $terpakai = (int) ($disetujui[$j->id] ?? 0) + (int) ($proses[$j->id] ?? 0);
            return (object) [
                'jenis' => $j,
                'kuota' => $j->kuota,
                'disetujui' => (int) ($disetujui[$j->id] ?? 0),
                'proses' => (int) ($proses[$j->id] ?? 0),
                'terpakai' => $terpakai,
                'sisa' => $j->kuota === null ? null : max(0, $j->kuota - $terpakai),
            ];
        })->keyBy(fn ($r) => $r->jenis->id);
    }
}
