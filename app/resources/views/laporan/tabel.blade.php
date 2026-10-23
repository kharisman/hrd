@extends('layout')
@section('title', $judul)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('laporan.index') }}" class="text-decoration-none">Laporan</a></li>
@endsection

@section('actions')
    <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Unduh Excel</a>
@endsection

@section('content')
<div class="card">
<form class="card-header bg-white row g-2 mx-0 py-3">
    @foreach ($filter as [$nama, $label, $opsi, $nilai])
        <div class="col-md-3">
            <select name="{{ $nama }}" class="form-select" onchange="this.form.submit()" aria-label="{{ $label }}">
                @foreach ($opsi as $v => $teks)<option value="{{ $v }}" @selected((string) $nilai === (string) $v)>{{ $teks }}</option>@endforeach
            </select>
        </div>
    @endforeach
    <div class="col text-md-end align-self-center small text-muted">{{ $baris->count() }} baris</div>
</form>
<div class="table-responsive" style="max-height:70vh">
<table class="table table-sm table-hover table-bordered mb-0 align-middle small">
    <thead class="table-light sticky-top"><tr><th style="width:40px">No</th>@foreach ($kolom as $k)<th class="text-nowrap">{{ $k }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($baris as $i => $b)
        <tr><td class="text-muted">{{ $i + 1 }}</td>@foreach ($b as $v)<td class="{{ is_int($v) ? 'text-end' : '' }}">{{ $v }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="{{ count($kolom) + 1 }}" class="text-center text-muted py-4">Tidak ada data untuk filter ini.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if ($catatan)<div class="card-footer small text-muted">{{ $catatan }}</div>@endif
</div>
@endsection
