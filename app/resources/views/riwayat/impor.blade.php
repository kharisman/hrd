@extends('layout')
@section('title', 'Impor Riwayat Pendidikan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('riwayat.index') }}" class="text-decoration-none">Studi Lanjut</a></li>
@endsection

@section('actions')
    <a href="{{ route('riwayat.template') }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Unduh Template</a>
@endsection

@section('content')
<form method="post" enctype="multipart/form-data" action="{{ route('riwayat.impor') }}" class="card mb-3" style="max-width:720px"><div class="card-body">
    @csrf
    <ol class="small text-muted ps-3">
        <li>Unduh template, isi satu baris per riwayat pendidikan. Orang dicocokkan lewat <strong>nip</strong>, <strong>nidn</strong>, atau <strong>username</strong>.</li>
        <li>Unggah dengan <strong>Cek dulu</strong> untuk melihat baris yang bermasalah (belum ada yang disimpan).</li>
        <li>Setelah beres, unggah lagi dengan <strong>Simpan</strong>. Baris yang sudah ada otomatis dilewati.</li>
    </ol>
    <div class="row g-2 align-items-end">
        <div class="col-md-7"><label class="form-label">File Excel / CSV</label><input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required></div>
        <div class="col-md-5 d-flex gap-2">
            <button name="mode" value="cek" class="btn btn-outline-primary flex-fill"><i class="bi bi-search me-1"></i>Cek dulu</button>
            <button name="mode" value="simpan" class="btn btn-primary flex-fill" onclick="return confirm('Simpan semua baris yang valid?')"><i class="bi bi-upload me-1"></i>Simpan</button>
        </div>
    </div>
</div></form>

@isset($baris)
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <strong>{{ $simpan ? 'Hasil impor' : 'Hasil cek (belum disimpan)' }}</strong>
        <span class="badge text-bg-success">{{ $ringkas['ok'] }} {{ $simpan ? 'tersimpan' : 'siap disimpan' }}</span>
        <span class="badge text-bg-warning">{{ $ringkas['lewati'] }} dilewati (sudah ada)</span>
        <span class="badge text-bg-danger">{{ $ringkas['gagal'] }} gagal</span>
    </div>
    <div class="table-responsive" style="max-height:60vh">
    <table class="table table-sm mb-0 align-middle">
        <thead class="sticky-top bg-white"><tr><th style="width:70px">Baris</th><th>Nama</th><th style="width:110px">Hasil</th><th>Keterangan</th></tr></thead>
        @forelse ($baris as $b)
            <tr class="{{ ['gagal' => 'table-danger', 'lewati' => 'table-warning'][$b['hasil']] ?? '' }}">
                <td>{{ $b['no'] }}</td>
                <td>{{ $b['nama'] ?? '—' }}</td>
                <td>{{ ['ok' => $simpan ? 'Tersimpan' : 'OK', 'lewati' => 'Dilewati', 'gagal' => 'Gagal'][$b['hasil']] }}</td>
                <td class="small">{{ $b['pesan'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-3">File tidak berisi data.</td></tr>
        @endforelse
    </table>
    </div>
</div>
@endisset
@endsection
