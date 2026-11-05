@extends('layout')
@section('title', 'Input Kehadiran & Keterlambatan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('input.index') }}" class="text-decoration-none">Input Data</a></li>
@endsection
@php($B = \App\Models\Kehadiran::BULAN)

@section('content')
<form class="bar-filter mb-3">
    <select name="tahun" class="form-select" onchange="this.form.submit()">@foreach (range(now()->year, now()->year - 5) as $t)<option @selected($t == $tahun)>{{ $t }}</option>@endforeach</select>
    <select name="bulan" class="form-select" onchange="this.form.submit()">@foreach ($B as $i => $b)<option value="{{ $i }}" @selected($i == $bulan)>{{ $b }}</option>@endforeach</select>
    <span class="info">Rekap {{ $B[$bulan] }} {{ $tahun }}</span>
</form>

<form method="post" action="{{ route('input.kehadiran.impor') }}" enctype="multipart/form-data" class="card mb-3" data-no-ajax><div class="card-body d-flex flex-wrap gap-2 align-items-center">
    @csrf <input type="hidden" name="tahun" value="{{ $tahun }}"><input type="hidden" name="bulan" value="{{ $bulan }}">
    <strong class="small me-2"><i class="bi bi-file-earmark-excel me-1"></i>Impor Excel</strong>
    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control form-control-sm" style="max-width:320px" required>
    <button class="btn btn-sm btn-primary">Impor ke {{ $B[$bulan] }} {{ $tahun }}</button>
    <a href="{{ route('input.kehadiran.template') }}" class="small" data-no-ajax>Unduh template</a>
    <span class="small text-muted">Kolom: NIP · Terlambat (menit) · Tidak hadir (hari)</span>
</div></form>

<form method="post" action="{{ route('dashboard.pegawai.simpan') }}" class="card">
    @csrf <input type="hidden" name="tahun" value="{{ $tahun }}"><input type="hidden" name="bulan" value="{{ $bulan }}">
    <div class="card-header bg-white small fw-semibold">Input manual</div>
    <div class="table-responsive" style="max-height:560px">
    <table class="table table-sm small mb-0 align-middle">
        <thead class="table-light sticky-top"><tr><th>Nama</th><th>Divisi</th><th style="width:150px">Terlambat (menit)</th><th style="width:150px">Tidak hadir (hari)</th></tr></thead>
        @foreach ($baris as $o)
            <tr><td>{{ $o->nama }}</td><td class="text-muted">{{ $o->divisi }}</td>
                <td><input name="terlambat[{{ $o->id }}]" type="number" min="0" value="{{ $isian[$o->id]->terlambat_menit ?? '' }}" class="form-control form-control-sm"></td>
                <td><input name="absen[{{ $o->id }}]" type="number" min="0" max="31" step="0.5" value="{{ isset($isian[$o->id]->tidak_hadir_hari) ? $isian[$o->id]->tidak_hadir_hari + 0 : '' }}" class="form-control form-control-sm"></td></tr>
        @endforeach
    </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="small text-muted">Isian kosong tidak mengubah data.</span>
        <button class="btn btn-primary">Simpan Rekap</button>
    </div>
</form>
@endsection
