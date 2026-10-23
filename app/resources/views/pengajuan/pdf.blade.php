<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $p->jenis->nama }}</title>
<style>
    @page { margin: 30mm 20mm 22mm 20mm; }
    /* Helvetica = font standar PDF (tidak disematkan) → file kecil */
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #222; }
    .kop { position: fixed; top: -22mm; left: 0; right: 0; border-bottom: 2px solid #0f2b5b; padding-bottom: 6px; }
    .kop img { height: 36px; vertical-align: middle; }
    .kop .teks { display: inline-block; vertical-align: middle; margin-left: 10px; }
    .kop .nama { font-size: 13pt; font-weight: bold; color: #0f2b5b; }
    .kop .sub { font-size: 8pt; color: #555; }
    .kaki { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 7pt; color: #888; border-top: 1px solid #ddd; padding-top: 4px; }
    h1 { font-size: 13.5pt; text-align: center; margin: 0 0 2px; text-transform: uppercase; letter-spacing: .5px; }
    .nomor { text-align: center; font-size: 9.5pt; color: #444; margin-bottom: 12px; }
    h2 { font-size: 10pt; margin: 14px 0 5px; color: #0f2b5b; border-bottom: 1px solid #ccd5e3; padding-bottom: 3px; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data td { padding: 4px 6px; vertical-align: top; }
    table.data td.l { width: 34%; color: #555; }
    table.data tr:nth-child(even) td { background: #f5f7fa; }
    .tanda-air { position: fixed; top: 40%; left: -10mm; right: -10mm; text-align: center; font-size: 54pt; font-weight: bold;
                 color: #c81e1e; opacity: .12; transform: rotate(-30deg); }
    .ttd { width: 66mm; margin-left: auto; margin-top: 20px; text-align: center; font-size: 10pt; page-break-inside: avoid; }
    .ttd img { width: 30mm; height: 30mm; margin: 6px 0 4px; }
    .ttd .nama { font-weight: bold; text-decoration: underline; }
    .ttd .ket { font-size: 6.8pt; color: #666; margin-top: 4px; line-height: 1.3; }
    .kosong { color: #999; }
</style>
</head>
<body>
<div class="kop">
    <img src="data:image/png;base64,{{ $logo }}" alt="">
    <div class="teks">
        <div class="nama">ITB PalComTech</div>
        <div class="sub">Institut Teknologi dan Bisnis PalComTech · Sumber Daya Manusia</div>
    </div>
</div>
<div class="kaki">
    Dicetak dari Sistem HRD ITB PalComTech pada {{ now()->translatedFormat('d F Y H:i') }}.
    @if ($p->kode_validasi) Keaslian dokumen dapat diperiksa dengan memindai kode QR pada tanda tangan. @endif
</div>

@if ($p->status !== 'Disetujui')
    <div class="tanda-air">{{ ['Ditolak' => 'DITOLAK', 'Dibatalkan' => 'DIBATALKAN'][$p->status] ?? 'BELUM DISETUJUI' }}</div>
@endif

<h1>{{ $p->jenis->nama }}</h1>
<div class="nomor">Nomor: {{ $p->nomor ?? '— (diberikan setelah disetujui)' }}</div>

<h2>Data Pengaju</h2>
<table class="data">
    <tr><td class="l">Nama</td><td>{{ $pengaju }}</td></tr>
    @unless ($pengaju === 'Anonim')
        <tr><td class="l">NIP</td><td>{{ $p->staff->nip ?? '-' }}</td></tr>
        <tr><td class="l">Jabatan / Divisi</td><td>{{ $p->staff->level ?? '-' }} / {{ $p->staff->divisi ?? '-' }}</td></tr>
    @endunless
    <tr><td class="l">Tanggal Pengajuan</td><td>{{ $p->created_at->translatedFormat('d F Y') }}</td></tr>
</table>

<h2>Rincian Pengajuan</h2>
<table class="data">
    @foreach ($p->jenis->isian as $f)
        @php($v = $p->data[$f['key']] ?? null)
        <tr><td class="l">{{ $f['label'] }}</td><td>
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

<h2>Riwayat Persetujuan</h2>
<table class="data">
    <tr><td class="l">Status</td><td><strong>{{ $p->status }}</strong></td></tr>
    @if ($p->atasan_at)
        <tr><td class="l">Atasan</td><td>{{ $p->atasan_oleh }}, {{ $p->atasan_at->translatedFormat('d F Y') }}{{ $p->atasan_catatan ? ' — ' . $p->atasan_catatan : '' }}</td></tr>
    @endif
    @if ($p->hrd_at)
        <tr><td class="l">HRD</td><td>{{ $p->hrd_oleh }}, {{ $p->hrd_at->translatedFormat('d F Y') }}{{ $p->hrd_catatan ? ' — ' . $p->hrd_catatan : '' }}</td></tr>
    @endif
</table>

{{-- Tanda tangan elektronik pejabat Rektor (hanya bila disetujui) --}}
@if ($p->status === 'Disetujui' && $qr)
    @php($ttd = $p->penandatangan ?? [])
    <div class="ttd">
        {{ $kota }}, {{ $p->disetujui_at->translatedFormat('d F Y') }}<br>
        {{ $ttd['jabatan'] ?? 'Rektor ITB PalComTech' }},<br>
        <img src="data:image/png;base64,{{ $qr }}" alt="QR tanda tangan"><br>
        <span class="nama">{{ $ttd['nama'] ?? '(pejabat Rektor belum diatur)' }}</span>
        @if (!empty($ttd['nip']))<br>NIP. {{ $ttd['nip'] }}@endif
        <div class="ket">Ditandatangani secara elektronik.<br>Pindai QR untuk memeriksa keaslian dokumen.</div>
    </div>
@endif
</body>
</html>
