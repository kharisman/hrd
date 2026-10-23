<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Alur: [Menunggu Atasan] → [Menunggu HRD] → Disetujui, atau Ditolak di tahap mana pun.
// Tahap yang tidak ada di alur jenisnya dilewati (mis. alur "HRD saja", atau pegawai belum punya atasan).
class Pengajuan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'pengajuan';
    protected $guarded = ['id'];
    protected $casts = ['data' => 'array', 'anonim' => 'boolean', 'atasan_at' => 'datetime', 'hrd_at' => 'datetime'];

    const MENUNGGU_ATASAN = 'Menunggu Atasan';
    const MENUNGGU_HRD = 'Menunggu HRD';
    const STATUS = [self::MENUNGGU_ATASAN, self::MENUNGGU_HRD, 'Disetujui', 'Ditolak', 'Dibatalkan'];
    const WARNA = ['Menunggu Atasan' => 'warning', 'Menunggu HRD' => 'info', 'Disetujui' => 'success', 'Ditolak' => 'danger', 'Dibatalkan' => 'secondary'];

    public function jenis()
    {
        return $this->belongsTo(JenisPengajuan::class, 'jenis_pengajuan_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    // Nama yang boleh ditampilkan: anonim disembunyikan dari selain pengaju sendiri
    public function namaPengaju(?int $viewerStaffId = null): string
    {
        return $this->anonim && $viewerStaffId !== $this->staff_id ? 'Anonim' : ($this->staff->nama ?? '#' . $this->staff_id);
    }

    // Status awal saat dikirim. Anonim langsung ke HRD (tidak lewat atasan).
    public static function statusAwal(JenisPengajuan $jenis, Staff $staff, bool $anonim): string
    {
        $lewatAtasan = in_array($jenis->alur, ['atasan_hrd', 'atasan']) && $staff->atasan_id && !$anonim;
        return $lewatAtasan ? self::MENUNGGU_ATASAN : self::MENUNGGU_HRD;
    }

    // Status setelah atasan menyetujui
    public function statusSetelahAtasan(): string
    {
        return $this->jenis->alur === 'atasan' ? 'Disetujui' : self::MENUNGGU_HRD;
    }

    public function selesai(): bool
    {
        return !in_array($this->status, [self::MENUNGGU_ATASAN, self::MENUNGGU_HRD]);
    }
}
