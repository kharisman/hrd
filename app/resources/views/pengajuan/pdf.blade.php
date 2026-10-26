<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $p->nomor ?? 'Draf' }} - {{ $p->jenis->nama }}</title>
{{-- Tata letak mengikuti surat BAAK (baaks/surat_generate/pdf): kop & footer gambar selebar halaman,
     Times New Roman 12pt, isi bermargin 25mm, blok TTD di kanan dengan isi rata kiri. --}}
<style>
    @page { size: A4; margin: 160px 0 140px 0; }   /* ruang untuk kop (atas) & footer (bawah) */
    body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; margin: 0; padding: 0; background: #fff; color: #000; }

    header { position: fixed; top: -160px; left: 0; right: 0; height: 150px; }
    footer { position: fixed; bottom: -140px; left: 0; right: 0; height: 140px; }
    header img, footer img { width: 100%; height: 100%; }

    main { margin-left: 25mm; margin-right: 25mm; line-height: 1.25; }

    .judul { text-align: center; font-weight: bold; font-size: 13pt; text-transform: uppercase; text-decoration: underline; margin: 0; }
    .nomor { text-align: center; margin: 2px 0 18px; }
    .bagian { font-weight: bold; margin: 14px 0 4px; }

    table.data { width: 100%; border-collapse: collapse; }
    table.data td { padding: 1px 0; vertical-align: top; }
    table.data td.l { width: 34%; }
    table.data td.t { width: 3%; }
    .kosong { color: #777; }

    .tanda-air { position: fixed; top: 34%; left: 0; right: 0; text-align: center; font-size: 56pt; font-weight: bold;
                 color: #c81e1e; opacity: .12; transform: rotate(-30deg); font-family: Helvetica, Arial, sans-serif; }

    /* Tanda tangan: blok di kanan, isi rata kiri (sama seperti surat BAAK) */
    .signature-section { margin-top: 36px; text-align: right; padding-right: 20px; page-break-inside: avoid; }
    /* lebar tetap: posisi TTD sama persis untuk draf & disetujui */
    .signature-inner { display: inline-block; text-align: left; width: 240px; word-wrap: break-word; }
    .signature-inner .qr-code { margin: 8px 0; }
    .signature-inner .qr-code img { width: 85px; height: 85px; }
    .belum { width: 85px; height: 85px; border: 1px dashed #bbb; text-align: center; font-size: 8pt; color: #999; }
    .belum span { display: block; padding-top: 36px; }
    .signature-name { font-weight: bold; }
    .signature-nip { font-size: 10.5pt; }
    .catatan-ttd { font-size: 8pt; color: #555; font-style: italic; margin-top: 4px; }
</style>
</head>
<body>
<header><img src="{{ public_path('img/kop-header.png') }}" alt="Kop ITB PalComTech"></header>
<footer><img src="{{ public_path('img/kop-footer.png') }}" alt=""></footer>

@if ($p->status !== 'Disetujui')
    <div class="tanda-air">{{ ['Ditolak' => 'DITOLAK', 'Dibatalkan' => 'DIBATALKAN'][$p->status] ?? 'BELUM DISETUJUI' }}</div>
@endif

<main>
    <p class="judul">{{ $p->jenis->nama }}</p>
    <p class="nomor">Nomor: {{ $p->nomor ?? '..../..../..../....' }}</p>

    <div class="bagian">Yang mengajukan:</div>
    <table class="data">
        <tr><td class="l">Nama</td><td class="t">:</td><td>{{ $pengaju }}</td></tr>
        @unless ($pengaju === 'Anonim')
            <tr><td class="l">NIP</td><td class="t">:</td><td>{{ $p->staff->nip ?? '-' }}</td></tr>
            <tr><td class="l">Jabatan / Unit</td><td class="t">:</td><td>{{ $p->staff->level ?? '-' }} / {{ $p->staff->divisi ?? '-' }}</td></tr>
        @endunless
        <tr><td class="l">Tanggal Pengajuan</td><td class="t">:</td><td>{{ $p->created_at->translatedFormat('d F Y') }}</td></tr>
    </table>

    <div class="bagian">Rincian pengajuan:</div>
    <table class="data">
        @if ($p->jenis_cuti_id)
            <tr><td class="l">Jenis Cuti</td><td class="t">:</td><td>{{ $p->jenisCuti->nama }}</td></tr>
            <tr><td class="l">Tanggal</td><td class="t">:</td><td>{{ $p->tgl_mulai->translatedFormat('d F Y') }} s.d. {{ $p->tgl_selesai->translatedFormat('d F Y') }} ({{ $p->hari }} hari)</td></tr>
        @endif
        @foreach ($p->jenis->isian as $f)
            @php($v = $p->data[$f['key']] ?? null)
            <tr><td class="l">{{ $f['label'] }}</td><td class="t">:</td><td>
                @if ($v === null || $v === '')
                    <span class="kosong">-</span>
                @elseif ($f['tipe'] === 'file')
                    Terlampir (berkas digital)
                @elseif ($f['tipe'] === 'rupiah')
                    Rp {{ number_format($v, 0, ',', '.') }}
                @elseif ($f['tipe'] === 'date')
                    {{ \Illuminate\Support\Carbon::parse($v)->translatedFormat('d F Y') }}
                @else
                    {!! nl2br(e($v)) !!}
                @endif
            </td></tr>
        @endforeach
    </table>

    <div class="bagian">Persetujuan:</div>
    <table class="data">
        <tr><td class="l">Status</td><td class="t">:</td><td><strong>{{ $p->status }}</strong></td></tr>
        @foreach ($p->riwayat ?? [] as $k)
            <tr><td class="l">{{ ['atasan' => 'Atasan Langsung', 'hrd' => 'Bagian SDM'][$k['tahap']] ?? \App\Models\JenisPengajuan::TAHAP[$k['tahap']] ?? $k['tahap'] }}</td><td class="t">:</td>
                <td>{{ $k['setuju'] ? 'Disetujui' : 'Ditolak' }} oleh {{ $k['oleh'] }}, {{ \Illuminate\Support\Carbon::parse($k['at'])->translatedFormat('d F Y') }}{{ $k['catatan'] ? ' (' . $k['catatan'] . ')' : '' }}</td></tr>
        @endforeach
    </table>

    {{-- Tanda tangan elektronik pejabat Rektor (QR menuju halaman validasi) --}}
    @php($ttd = $p->penandatangan ?? [])
    <div class="signature-section">
        <div class="signature-inner">
            <div style="margin-bottom:4px">{{ $kota }}, {{ ($p->disetujui_at ?? now())->translatedFormat('d F Y') }}</div>
            <div>Hormat kami,</div>
            <div style="font-weight:bold">{{ $ttd['jabatan'] ?? 'Rektor ITB PalComTech' }},</div>
            <div class="qr-code">
                @if ($p->status === 'Disetujui' && $qr)
                    <img src="data:image/png;base64,{{ $qr }}" alt="QR tanda tangan">
                @else
                    <div class="belum"><span>[Belum disetujui]</span></div>
                @endif
            </div>
            <div class="signature-name">{{ $ttd['nama'] ?? optional(\App\Models\Pengaturan::penandatangan())->nama ?? 'Rektor ITB PalComTech' }}</div>
            <div class="signature-nip">NIP. {{ $ttd['nip'] ?? optional(\App\Models\Pengaturan::penandatangan())->nip ?? '-' }}</div>
            @if ($p->status === 'Disetujui' && $qr)
                <div class="catatan-ttd">Ditandatangani secara elektronik. Pindai QR untuk memeriksa keaslian.</div>
            @endif
        </div>
    </div>
</main>
</body>
</html>
