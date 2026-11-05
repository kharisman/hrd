<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $p->nomor ?? 'Draf' }} - Cuti {{ $p->staff->nama ?? '' }}</title>
{{-- Format resmi HRD: hal. 1 "Form Pengajuan Cuti" (FM/04/INSTITUT/HRD/07-001), hal. 2 "Surat Persetujuan Pengajuan Cuti"
     (hanya bila disetujui). Disalin dari dokumen Word HRD; kolom merah di template = data pengajuan. --}}
<style>
    @page { size: A4; margin: 18mm 20mm; }
    body { font-family: Calibri, Helvetica, Arial, sans-serif; font-size: 11pt; color: #000; margin: 0; }
    table { border-collapse: collapse; }
    .kop td { border: 1px solid #000; vertical-align: top; }
    .kotak { display: inline-block; width: 16px; height: 16px; border: 1px solid #000; text-align: center; line-height: 16px; font-weight: bold; margin-right: 8px; vertical-align: middle; font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; }
    .data td { padding: 1px 4px 1px 0; vertical-align: top; }
    .garis { border-bottom: 1px solid #000; display: inline-block; min-width: 80px; text-align: center; }
    .ttd { width: 85px; height: 85px; }
    .belum { width: 85px; height: 85px; border: 1px dashed #999; color: #999; font-size: 8pt; text-align: center; }
    .tanda-air { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 54pt; font-weight: bold; color: #c81e1e; opacity: .12; transform: rotate(-30deg); }
    .surat { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; }
    .surat .data td { padding: 0 4px 0 0; }
    .surat .data td.l { width: 200px; }
    .kecil { font-size: 8pt; color: #555; font-style: italic; }
</style>
</head>
<body>
@php
    $tgl = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d F Y') : '-';
    $nama = $p->jenisCuti->nama ?? '';
    $kategori = str_contains(strtolower($nama), 'tahunan') ? 'Tahunan' : (str_contains(strtolower($nama), 'sakit') ? 'Sakit' : 'Khusus');
    $centang = fn ($k) => $kategori === $k ? '&#10003;' : '&nbsp;';
    $koreksi = collect($p->riwayat)->contains(fn ($k) => str_contains($k['catatan'] ?? '', 'Dijadwal ulang') || str_contains($k['catatan'] ?? '', 'Lama '));
    $setuju = $p->status === 'Disetujui';
    $ttdImg = $setuju && $qr ? '<img class="ttd" src="data:image/png;base64,' . $qr . '">' : '<div class="belum"><br><br>[Belum disetujui]</div>';
@endphp

@unless ($setuju)
    <div class="tanda-air">{{ ['Ditolak' => 'DITOLAK', 'Dibatalkan' => 'DIBATALKAN'][$p->status] ?? 'BELUM DISETUJUI' }}</div>
@endunless

{{-- ========== Halaman 1: Form Pengajuan Cuti ========== --}}
<table class="kop" style="width:100%">
    <tr>
        <td style="width:32%;text-align:center;padding:4px">
            <img src="{{ public_path('img/logo-institut.png') }}" style="height:62px"><br>
            <span style="font-size:10pt">Kode Formulir :<br>FM/04/INSTITUT/HRD/07-001</span>
        </td>
        <td style="text-align:center;font-size:14pt;padding-top:18px">FORM PENGAJUAN CUTI</td>
    </tr>
</table>

<table class="data" style="width:100%;margin-top:22px">
    <tr><td style="width:13%">Nama</td><td style="width:37%">: {{ $p->staff->nama ?? '-' }}</td><td style="width:22%">Cuti mulai tanggal</td><td>: {{ $tgl($p->tgl_mulai) }}</td></tr>
    <tr><td>Jabatan</td><td>: {{ $p->staff->level ?? '-' }}</td><td>Sampai tanggal</td><td>: {{ $tgl($p->tgl_selesai) }}</td></tr>
    <tr><td>Divisi</td><td>: {{ $p->staff->divisi ?? '-' }}</td><td>Total hari</td><td>: {{ $p->hari }} Hari</td></tr>
    <tr><td></td><td></td><td>Tanggal di ajukan</td><td>: {{ $tgl($p->created_at) }}</td></tr>
</table>

<table style="width:100%;margin-top:14px">
    <tr>@foreach (['Tahunan', 'Sakit', 'Khusus'] as $k)<td><span class="kotak">{!! $centang($k) !!}</span> Cuti {{ $k }}{{ $k === 'Khusus' && $kategori === 'Khusus' ? " ({$nama})" : '' }}</td>@endforeach</tr>
</table>

<p style="margin-top:12px">Keterangan cuti : <span style="border-bottom:1px solid #000">{{ $p->data['alasan'] ?? '-' }}</span></p>

<table style="width:92%;margin:16px auto 0;border:1px solid #000">
    <tr>
        <td style="width:50%;border-right:1px solid #000;padding:12px;vertical-align:top;height:140px">
            No telp yang dapat dihubungi selama cuti :<br><strong>{{ $p->staff->no_hp ?: '-' }}</strong><br><br>
            Selama menjalankan cuti, pekerjaan di serahkan kepada :<br>
            <strong>{{ $pengganti->nama ?? '-' }}</strong><br>{{ $pengganti->no_hp ?? '' }}
        </td>
        <td style="padding:12px;vertical-align:top">
            Tanda Tangan :<br><br>
            <span class="kecil">Diajukan secara elektronik melalui aplikasi HRD<br>pada {{ $p->created_at->translatedFormat('d F Y H:i') }}</span><br><br>
            <strong>{{ $p->staff->nama ?? '' }}</strong>
        </td>
    </tr>
</table>

<p style="margin-top:26px">Keputusan Management:</p>
<p><span class="kotak">{!! $setuju && !$koreksi ? '&#10003;' : '&nbsp;' !!}</span> Cuti di setujui penuh</p>
<p><span class="kotak">{!! $setuju && $koreksi ? '&#10003;' : '&nbsp;' !!}</span> Cuti akan diberikan
    <span class="garis">{{ $setuju && $koreksi ? $p->hari : '' }}</span> hari kerja, Mulai <span class="garis">{{ $setuju && $koreksi ? $p->tgl_mulai->format('d/m/Y') : '' }}</span>
    sampai dengan <span class="garis">{{ $setuju && $koreksi ? $p->tgl_selesai->format('d/m/Y') : '' }}</span></p>
<p><span class="kotak">{!! $p->status === 'Ditolak' ? '&#10003;' : '&nbsp;' !!}</span>
    <span style="border-bottom:1px solid #000;display:inline-block;width:88%">{{ $p->status === 'Ditolak' ? 'Ditolak: ' . (collect($p->riwayat)->last()['catatan'] ?? '-') : '' }}</span></p>

<table style="width:100%;margin-top:20px"><tr><td style="width:62%"></td><td>
    {{ $kota }}, {{ $tgl($p->disetujui_at ?? now()) }}<br>
    {!! $ttdImg !!}<br>
    <strong style="text-decoration:underline">{{ $rektor['nama'] ?? 'Rektor' }}</strong><br>Rektor
</td></tr></table>

{{-- ========== Halaman 2: Surat Persetujuan (hanya bila disetujui) ========== --}}
@if ($setuju)
<div style="page-break-before:always" class="surat">
    <img src="{{ public_path('img/kop-institut.png') }}" style="height:70px"><br>
    Kode Dokumen : FM/04/INSTITUT/HRD/07-001

    <p style="text-align:center;font-weight:bold;font-size:14pt;margin:18px 0 2px">Surat Persetujuan Pengajuan Cuti</p>
    <p style="text-align:center;margin:0 0 12px">Nomor: {{ $p->nomor }}</p>

    <p style="text-align:justify;margin:0">Berdasarkan pengajuan cuti {{ $kategori }} saudara dan telah mendapat persetujuan dari Pimpinan INSTITUT PalComTech,
        maka cuti {{ $kategori === 'Khusus' ? "Khusus ({$nama})" : $kategori }} diberikan selama {{ $p->hari }} hari kepada :</p>
    <table class="data">
        <tr><td class="l">Nama</td><td>: {{ $p->staff->nama ?? '-' }}</td></tr>
        <tr><td class="l">Divisi</td><td>: {{ $p->staff->divisi ?? '-' }}</td></tr>
    </table>
    <p style="text-align:justify;margin:0">Berdasarkan rencana cuti {{ strtolower($kategori) }} dan telah mendapat persetujuan dari atasannya, maka kepada yang bersangkutan
        diberikan cuti {{ strtolower($kategori) }} selama {{ $p->hari }} hari.</p>
    <table class="data">
        <tr><td class="l">Mulai cuti tanggal</td><td>: {{ $tgl($p->tgl_mulai) }}</td></tr>
        <tr><td class="l">Berakhir cuti tanggal</td><td>: {{ $tgl($p->tgl_selesai) }}</td></tr>
        <tr><td class="l">Bekerja kembali tanggal</td><td>: {{ $tgl($kembali) }}</td></tr>
    </table>
    <br>
    Selama menjalankan cuti, pekerjaan diserahkan kepada :
    <table class="data">
        <tr><td class="l">Nama</td><td>: {{ $pengganti->nama ?? '-' }}</td></tr>
        <tr><td class="l">Divisi</td><td>: {{ $pengganti->divisi ?? '-' }}</td></tr>
    </table>
    <br>
    <table class="data">
        <tr><td class="l">Kontak Person selama cuti</td><td>:</td></tr>
        <tr><td class="l">Telp/HP</td><td>: {{ $p->staff->no_hp ?: '-' }}</td></tr>
    </table>
    <p>Demikian surat persetujuan cuti {{ strtolower($kategori) }} ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

    <div style="margin-top:16px">
        {{ $kota }}, {{ $tgl($p->disetujui_at) }}<br>INSTITUT PalComTech<br>
        {!! $ttdImg !!}<br>
        <strong style="text-decoration:underline">{{ $warek2->nama ?? '........................................' }}</strong><br>Wakil Rektor 2
    </div>
    <p class="kecil" style="margin-top:6px">Ditandatangani secara elektronik. Pindai QR untuk memeriksa keaslian.</p>

    <p style="margin-top:14px;font-style:italic">Note :<br>Memperpanjang cuti secara sepihak tanpa mendapat persetujuan dari HRD, akan diberikan sanksi.</p>
</div>
@endif
</body>
</html>
