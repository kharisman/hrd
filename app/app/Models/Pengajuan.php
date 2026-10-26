<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Alur: urutan tahap dari jenisnya (mis. atasan → keuangan → hrd), disalin ke kolom tahapan saat diajukan.
// Status "Menunggu <Tahap>" bergeser satu per satu → Disetujui, atau Ditolak di tahap mana pun.
// Tahap atasan dilewati bila pegawai belum punya atasan atau pengajuan anonim.
class Pengajuan extends Model
{
    protected $connection = 'mysql';
    protected $table = 'pengajuan';
    protected $guarded = ['id'];
    protected $casts = ['data' => 'array', 'anonim' => 'boolean', 'tahapan' => 'array', 'riwayat' => 'array',
        'disetujui_at' => 'datetime', 'penandatangan' => 'array', 'tgl_mulai' => 'date', 'tgl_selesai' => 'date'];

    const SELESAI = ['Disetujui', 'Ditolak', 'Dibatalkan'];

    public static function menunggu(string $tahap): string
    {
        return 'Menunggu ' . (JenisPengajuan::TAHAP[$tahap] ?? $tahap);
    }

    // Semua status untuk filter: Menunggu <tiap tahap> + status akhir
    public static function semuaStatus(): array
    {
        return [...array_map([self::class, 'menunggu'], array_keys(JenisPengajuan::TAHAP)), ...self::SELESAI];
    }

    public static function warna(string $status): string
    {
        return ['Disetujui' => 'success', 'Ditolak' => 'danger', 'Dibatalkan' => 'secondary'][$status]
            ?? ($status === self::menunggu('atasan') ? 'warning' : 'info');
    }

    public function scopeMenunggu($q)
    {
        return $q->whereNotIn('status', self::SELESAI);
    }

    /**
     * Pengajuan yang sedang menunggu keputusan orang ini: bawahannya di tahap atasan, peran pejabatnya,
     * atau tahap HRD bila admin. Pengajuan miliknya sendiri tidak termasuk.
     */
    public function scopeTugas($q, $staffIds, bool $admin)
    {
        $staffIds = collect($staffIds);
        $bawahan = $staffIds->isEmpty() ? collect() : Staff::whereIn('atasan_id', $staffIds)->pluck('id');
        $status = Pejabat::peranDari($staffIds)->map([self::class, 'menunggu'])->when($admin, fn ($s) => $s->push(self::menunggu('hrd')));
        return $q->whereNotIn('staff_id', $staffIds)->where(fn ($w) => $w->whereIn('status', $status)
            ->orWhere(fn ($x) => $x->where('status', self::menunggu('atasan'))->whereIn('staff_id', $bawahan)->where('anonim', false)));
    }

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

    // Kolom alur saat dikirim: salinan tahapan jenis, tanpa tahap atasan bila tak punya atasan / anonim
    public static function alurAwal(JenisPengajuan $jenis, Staff $staff, bool $anonim): array
    {
        $tahapan = array_values(array_filter($jenis->tahapan ?: ['hrd'], fn ($t) => $t !== 'atasan' || ($staff->atasan_id && !$anonim)));
        $tahapan = $tahapan ?: ['hrd']; // alur "Atasan saja" tanpa atasan → HRD yang memutus
        return ['tahapan' => $tahapan, 'tahap_ke' => 0, 'riwayat' => [], 'status' => self::menunggu($tahapan[0])];
    }

    public function tahapSekarang(): ?string
    {
        return $this->selesai() ? null : ($this->tahapan[$this->tahap_ke] ?? null);
    }

    // Catat keputusan tahap sekarang; setuju di tahap terakhir → Disetujui + nomor surat & QR
    public function putuskan(bool $setuju, string $oleh, ?string $catatan): void
    {
        $tahap = $this->tahapSekarang();
        $riwayat = [...($this->riwayat ?? []), ['tahap' => $tahap, 'oleh' => $oleh, 'at' => now()->toDateTimeString(), 'catatan' => $catatan, 'setuju' => $setuju]];
        $berikut = $this->tahap_ke + 1;
        $this->update(['riwayat' => $riwayat] + match (true) {
            !$setuju => ['status' => 'Ditolak'],
            $berikut >= count($this->tahapan) => ['status' => 'Disetujui', 'tahap_ke' => $berikut],
            default => ['status' => self::menunggu($this->tahapan[$berikut]), 'tahap_ke' => $berikut],
        });
        if ($this->status === 'Disetujui') {
            $this->sahkan();
        }
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
        return in_array($this->status, self::SELESAI);
    }
}
