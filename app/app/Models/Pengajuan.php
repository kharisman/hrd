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
    protected $casts = ['data' => 'array', 'anonim' => 'boolean', 'atasan_at' => 'datetime', 'hrd_at' => 'datetime',
        'disetujui_at' => 'datetime', 'penandatangan' => 'array', 'tgl_mulai' => 'date', 'tgl_selesai' => 'date'];

    const MENUNGGU_ATASAN = 'Menunggu Atasan';
    const MENUNGGU_HRD = 'Menunggu HRD';
    const MENUNGGU = [self::MENUNGGU_ATASAN, self::MENUNGGU_HRD];
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

    // Khusus jenis Cuti & Izin
    public function jenisCuti()
    {
        return $this->belongsTo(JenisCuti::class);
    }

    public function scopeCuti($q)
    {
        return $q->whereNotNull('jenis_cuti_id');
    }

    public function getHariAttribute(): ?int
    {
        return $this->tgl_mulai ? $this->tgl_mulai->diffInDays($this->tgl_selesai) + 1 : null;
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

    /**
     * Dipanggil saat pengajuan disetujui di tahap akhir: beri nomor surat urut per tahun,
     * kode validasi acak untuk QR, dan salinan penandatangan saat itu (dokumen lama tidak ikut berubah
     * bila penandatangan diganti).
     */
    public function sahkan(): void
    {
        if ($this->nomor) {
            return;
        }
        $pejabat = Pengaturan::penandatangan();
        $tahun = now()->year;
        $urut = static::whereYear('disetujui_at', $tahun)->count() + 1;
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];

        $this->update([
            'nomor' => sprintf('%03d/%s/%s/%d', $urut, Pengaturan::nilai('kode_surat', 'HRD'), $romawi, $tahun),
            'kode_validasi' => \Illuminate\Support\Str::random(32),
            'disetujui_at' => now(),
            // Salinan saat disetujui: dokumen lama tetap atas nama pejabat saat itu walau Rektor berganti
            'penandatangan' => [
                'staff_id' => $pejabat?->id,
                'nama' => $pejabat?->nama,
                'jabatan' => ($pejabat?->level ?? 'Rektor') . ' ITB PalComTech',
                'nip' => $pejabat?->nip,
            ],
        ]);
    }

    public function selesai(): bool
    {
        return !in_array($this->status, [self::MENUNGGU_ATASAN, self::MENUNGGU_HRD]);
    }
}
