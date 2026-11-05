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
        'disetujui_at' => 'datetime', 'penandatangan' => 'array', 'tgl_mulai' => 'date', 'tgl_selesai' => 'date', 'notifikasi' => 'array'];

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
        // ponytail: cek kaprodi per pengajuan di PHP (jumlah "Menunggu Kaprodi" kecil); pindah ke query bila banyak
        $kaprodi = $staffIds->isEmpty() ? [] : static::where('status', self::menunggu('kaprodi'))->get(['id', 'staff_id'])
            ->filter(fn ($p) => self::kaprodiUntuk($p->staff_id)->intersect($staffIds)->isNotEmpty())->pluck('id')->all();
        return $q->whereNotIn('staff_id', $staffIds)->where(fn ($w) => $w->whereIn('status', $status)->orWhereIn('id', $kaprodi)
            ->orWhere(fn ($x) => $x->where('status', self::menunggu('atasan'))->whereIn('staff_id', $bawahan)->where('anonim', false)));
    }

    /**
     * Kaprodi pengaju: pegawai ber-role/level kaprodi yang dosen aktifnya satu prodi (homebase) dengan dosen pengaju.
     * Aturan sama dengan Akses::prodiDosen. @return \Illuminate\Support\Collection<int> staff id (semua akun ber-NIP sama)
     */
    public static function kaprodiUntuk(?int $staffId)
    {
        $nip = Staff::whereKey($staffId)->value('nip');
        $milik = $nip ? Staff::where('nip', $nip)->pluck('id') : collect([$staffId]);
        $prodi = Dosen::where('aktif', true)->whereIn('staff_id', $milik)->whereNotNull('program_studi')->value('program_studi');
        if (!$prodi) {
            return collect();
        }
        $nipDosen = Staff::whereIn('id', Dosen::where('aktif', true)->where('program_studi', $prodi)->whereNotNull('staff_id')->pluck('staff_id'))->pluck('nip')->filter();
        $kaprodiNip = Staff::whereIn('nip', $nipDosen)->where('aktif', true)->get()
            ->filter(fn ($s) => in_array('kaprodi', (array) $s->roles) || strcasecmp((string) $s->level, 'Kaprodi') === 0)->pluck('nip')->unique();
        return Staff::whereIn('nip', $kaprodiNip)->pluck('id');
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
        return $this->tgl_mulai ? ($this->lama ?? $this->tgl_mulai->diffInDays($this->tgl_selesai) + 1) : null;
    }

    // Nama yang boleh ditampilkan: anonim disembunyikan dari selain pengaju sendiri
    public function namaPengaju(?int $viewerStaffId = null): string
    {
        return $this->anonim && $viewerStaffId !== $this->staff_id ? 'Anonim' : ($this->staff->nama ?? '#' . $this->staff_id);
    }

    // Kolom alur saat dikirim: salinan tahapan jenis, tanpa tahap atasan bila tak punya atasan / anonim
    public static function alurAwal(JenisPengajuan $jenis, Staff $staff, bool $anonim): array
    {
        // Tahap atasan/kaprodi dilewati bila tidak ada orangnya, pengajuan anonim, atau pengaju sendiri kaprodinya
        $kaprodi = in_array('kaprodi', $jenis->tahapan ?? []) ? self::kaprodiUntuk($staff->id) : collect();
        $tahapan = array_values(array_filter($jenis->tahapan ?: ['hrd'], fn ($t) => match ($t) {
            'atasan' => $staff->atasan_id && !$anonim,
            'kaprodi' => !$anonim && $kaprodi->isNotEmpty() && !$kaprodi->contains($staff->id),
            default => true,
        }));
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
            $this->jadikanCapaian();
        }
        $this->kabari();
    }

    // Akun yang memutus tahap sekarang (semua akun ber-NIP sama dengan pemegangnya)
    // Username pemutus tahap sekarang: akun HRD + username master pegawai (yang belum pernah login ke HRD tetap dikabari)
    public function penerimaTahap()
    {
        $tahap = $this->tahapSekarang();
        if (!$tahap) {
            return collect();
        }
        if ($tahap === 'hrd') {
            return Akun::where('peran', 'admin')->pluck('username');
        }
        $ids = ($tahap === 'kaprodi' ? self::kaprodiUntuk($this->staff_id)->take(1)
            : ($tahap === 'atasan' ? collect([$this->staff?->atasan_id])
            : Pejabat::where('peran', $tahap)->pluck('staff_id')->merge(Pejabat::dariRole($tahap)->pluck('id'))
                ->when($tahap === 'rektor', fn ($c) => $c->push(Pengaturan::penandatangan()?->id))))->filter();
        return self::usernamePegawai($ids)->diff(self::usernamePegawai([$this->staff_id]))->values();
    }

    // Satu username per pegawai (supaya tidak dapat pesan ganda): akun HRD yang terakhir login, kalau belum pernah login
    // username data pegawai yang ditunjuk itu sendiri (mis. pejabat Warek II = tata_2), terakhir staff utama ber-NIP sama
    private static function usernamePegawai($staffIds)
    {
        return Staff::whereIn('id', $staffIds)->get()->map(function ($s) {
            $ids = $s->nip ? Staff::where('nip', $s->nip)->pluck('id') : collect([$s->id]);
            return Akun::whereIn('staff_id', $ids)->orderByDesc('last_login_at')->value('username')
                ?? $s->username ?? ($s->nip ? Staff::utama($s->nip)?->username : null);
        })->filter()->unique()->values();
    }

    // Notifikasi Telegram: pemutus tahap berikutnya, atau pengaju bila sudah selesai (disetujui / ditolak)
    public function kabari(): void
    {
        $tautan = fn ($teks) => ['teks' => $teks, 'url' => route('pengajuan.show', $this)];
        $judul = e($this->jenis->nama ?? 'Pengajuan') . ($this->jenis_cuti_id ? ' (' . e($this->jenisCuti->nama ?? '') . ', ' . $this->tgl_mulai->format('d/m/Y') . ' – ' . $this->tgl_selesai->format('d/m/Y') . ", {$this->hari} hari)" : '');
        if ($this->selesai()) {
            $catatan = collect($this->riwayat)->last()['catatan'] ?? null;
            $log[] = $this->kirimNotif('Pengaju', self::usernamePegawai([$this->staff_id]),
                ($this->status === 'Disetujui' ? '✅' : '❌') . " {$judul} <b>{$this->status}</b>" . ($this->nomor ? "\nNomor: {$this->nomor}" : '')
                . ($catatan ? "\nCatatan: " . e($catatan) : ''), $tautan('Lihat pengajuan'), "hrd-pengajuan-{$this->id}-selesai");
            // Cuti disetujui → pegawai pengganti diberi tahu (isian "pengganti": "Nama · Divisi · NIP")
            $nip = $this->status === 'Disetujui' ? JenisPengajuan::nipPegawai($this->data['pengganti'] ?? null) : null;
            if ($nip && ($pengganti = Staff::utama($nip))) {
                $log[] = $this->kirimNotif('Pengganti', self::usernamePegawai([$pengganti->id]),
                    "🤝 Anda ditunjuk sebagai <b>pegawai pengganti</b> selama " . e($this->jenisCuti->nama ?? 'cuti') . " <b>" . e($this->staff->nama ?? '') . "</b>"
                    . " (" . e($this->staff->divisi ?? '') . "), {$this->tgl_mulai->format('d/m/Y')} – {$this->tgl_selesai->format('d/m/Y')}."
                    . ($this->staff?->no_hp ? "\nKontak: " . e($this->staff->no_hp) : ''), null, "hrd-pengajuan-{$this->id}-pengganti");
            }
        } else {
            $log[] = $this->kirimNotif(JenisPengajuan::TAHAP[$this->tahapSekarang()] ?? $this->tahapSekarang(), $this->penerimaTahap(),
                "📝 {$judul} dari <b>" . e($this->namaPengaju()) . "</b> menunggu persetujuan Anda ({$this->status}).",
                $tautan('Proses sekarang'), "hrd-pengajuan-{$this->id}-tahap-{$this->tahap_ke}");
        }
        $this->updateQuietly(['notifikasi' => [...($this->notifikasi ?? []), ...$log]]);
    }

    /**
     * Pengingat manual dari pengaju ke pemutus tahap sekarang. Dibatasi sekali per 6 jam per tahap.
     * @return ?string waktu boleh mengingatkan lagi (null = boleh sekarang)
     */
    public function jedaIngat(): ?string
    {
        $akhir = collect($this->notifikasi)->where('jenis', 'ingat')->where('tahap_ke', $this->tahap_ke)->last();
        $lagi = $akhir ? \Illuminate\Support\Carbon::parse($akhir['at'])->addHours(6) : null;
        return $lagi?->isFuture() ? $lagi->translatedFormat('d M H:i') : null;
    }

    public function ingatkan(): array
    {
        $judul = e($this->jenis->nama ?? 'Pengajuan') . ($this->jenis_cuti_id ? ' (' . e($this->jenisCuti->nama ?? '') . ', ' . $this->tgl_mulai->format('d/m/Y') . ' – ' . $this->tgl_selesai->format('d/m/Y') . ')' : '');
        $hari = $this->updated_at->diffInDays() ?: null;
        $log = $this->kirimNotif(JenisPengajuan::TAHAP[$this->tahapSekarang()] ?? $this->tahapSekarang(), $this->penerimaTahap(),
            "⏰ <b>Pengingat</b> dari " . e($this->namaPengaju()) . ": {$judul} masih menunggu persetujuan Anda" . ($hari ? " sejak {$hari} hari lalu" : '') . '.',
            ['teks' => 'Proses sekarang', 'url' => route('pengajuan.show', $this)], "hrd-pengajuan-{$this->id}-ingat-{$this->tahap_ke}-" . now()->format('YmdH'));
        $this->updateQuietly(['notifikasi' => [...($this->notifikasi ?? []), $log + ['jenis' => 'ingat', 'tahap_ke' => $this->tahap_ke]]]);
        return $log;
    }

    // Kirim + baris log (id API untuk dilacak lewat GET /notifikasi/{id}); error "tidak terkirim" = API tak terjangkau / tanpa penerima
    private function kirimNotif(string $untuk, $kepada, string $pesan, ?array $tautan, string $kunci): array
    {
        $hasil = \App\Services\Telegram::kirim($kepada, $pesan, $tautan, $kunci);
        return [
            'untuk' => $untuk, 'at' => now()->toDateTimeString(), 'kepada' => collect($kepada)->values()->all(),
            'id' => $hasil['id'] ?? null, 'terkirim' => $hasil['terkirim'] ?? [],
            'dilewati' => collect($hasil['dilewati'] ?? [])->merge($hasil['gagal'] ?? [])->all(),
            'error' => $hasil === null ? 'tidak terkirim' : ($hasil['pesan'] ?? $hasil['error'] ?? null),
        ];
    }

    // "Laporan Capaian Dosen" disetujui → masuk rapor dosen pengaju (satu pengajuan = satu capaian)
    private function jadikanCapaian(): void
    {
        if ($this->jenis?->kode !== 'capaian') {
            return;
        }
        $nip = Staff::whereKey($this->staff_id)->value('nip');
        $dosen = Dosen::whereIn('staff_id', Staff::where('nip', $nip ?? '-')->pluck('id')->push($this->staff_id))->orderByDesc('aktif')->first();
        if (!$dosen) {
            return; // pengaju bukan dosen: tetap tercatat sebagai pengajuan saja
        }
        $d = $this->data;
        $tgl = !empty($d['tanggal']) ? \Illuminate\Support\Carbon::parse($d['tanggal']) : null;
        CapaianDosen::firstOrCreate(['pengajuan_id' => $this->id], [
            'dosen_id' => $dosen->id,
            'jenis' => array_search($d['jenis'] ?? '', CapaianDosen::JENIS) ?: 'sertifikat',
            'judul' => $d['judul'] ?? '-', 'sumber' => $d['sumber'] ?? null, 'status' => $d['status'] ?? null,
            'tahun' => $tgl?->year, 'bulan' => $tgl?->month, 'link' => $d['link'] ?? null,
            'keterangan' => 'Dari pengajuan ' . $this->nomor,
        ]);
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
