<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Validasi Dokumen · ITB PalComTech</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:560px">
    <div class="text-center mb-3">
        <img src="{{ asset('logo-pct1.png') }}" height="48" alt="ITB PalComTech">
        <div class="text-muted small mt-2">Validasi Dokumen Elektronik · Sistem HRD</div>
    </div>

    @if ($p && $p->status === 'Disetujui')
        @php($ttd = $p->penandatangan ?? [])
        <div class="card border-success">
            <div class="card-body text-center bg-success-subtle">
                <i class="bi bi-patch-check-fill text-success" style="font-size:3rem"></i>
                <h4 class="text-success mb-1">Dokumen SAH</h4>
                <div class="small text-muted">Dokumen ini benar diterbitkan dan ditandatangani secara elektronik melalui Sistem HRD ITB PalComTech.</div>
            </div>
            <table class="table mb-0">
                <tr><th style="width:42%">Nomor</th><td>{{ $p->nomor }}</td></tr>
                <tr><th>Jenis Dokumen</th><td>{{ $p->jenis->nama }}</td></tr>
                <tr><th>Pengaju</th><td>{{ $p->anonim ? 'Anonim' : ($p->staff->nama ?? '-') }}
                    @unless ($p->anonim)<br><small class="text-muted">{{ $p->staff->level ?? '' }} · {{ $p->staff->divisi ?? '' }}</small>@endunless</td></tr>
                <tr><th>Tanggal Pengajuan</th><td>{{ $p->created_at->translatedFormat('d F Y') }}</td></tr>
                <tr><th>Tanggal Disetujui</th><td>{{ $p->disetujui_at->translatedFormat('d F Y H:i') }}</td></tr>
                <tr><th>Ditandatangani oleh</th><td>{{ $ttd['nama'] ?? '-' }}<br><small class="text-muted">{{ $ttd['jabatan'] ?? '' }}{{ !empty($ttd['nip']) ? ' · NIP ' . $ttd['nip'] : '' }}</small></td></tr>
            </table>
            <div class="card-footer small text-muted">
                Pastikan nomor, jenis, nama, dan tanggal pada dokumen yang Anda pegang sama dengan data di atas.
                Rincian isi pengajuan tidak ditampilkan di halaman ini.
            </div>
        </div>
    @elseif ($p)
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:3rem"></i>
                <h4 class="mb-1">Dokumen Tidak Berlaku</h4>
                <div class="text-muted">Dokumen nomor {{ $p->nomor }} berstatus <strong>{{ $p->status }}</strong>.</div>
            </div>
        </div>
    @else
        <div class="card border-danger">
            <div class="card-body text-center">
                <i class="bi bi-x-octagon-fill text-danger" style="font-size:3rem"></i>
                <h4 class="text-danger mb-1">Dokumen Tidak Dikenal</h4>
                <div class="text-muted">Kode validasi tidak terdaftar. Dokumen ini kemungkinan bukan dokumen resmi ITB PalComTech.</div>
            </div>
        </div>
    @endif
</div>
</body>
</html>
