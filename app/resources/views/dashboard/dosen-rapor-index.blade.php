@extends('layout')
@section('title', 'Rapor Dosen')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.dosen') }}" class="text-decoration-none">Dashboard Dosen</a></li>
@endsection

@section('content')
@include('dashboard._tab-dosen')

<ul class="nav nav-pills mb-3 small">
    <li class="nav-item"><a class="nav-link {{ !$prodi ? 'active' : '' }}" href="{{ route('dashboard.dosen.rapor.index') }}">Semua Prodi</a></li>
    @foreach (\App\Models\Dosen::daftarProdi() as $p)
        <li class="nav-item"><a class="nav-link {{ $prodi === $p ? 'active' : '' }}" href="{{ route('dashboard.dosen.rapor.index', ['prodi' => $p]) }}">{{ $p }}</a></li>
    @endforeach
</ul>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover small mb-0 align-middle">
        <thead class="table-light"><tr><th>Dosen</th>@unless ($prodi)<th>Prodi</th>@endunless<th>Roadmap</th>
            @foreach (['sertifikat' => 'Sertifikat', 'prestasi' => 'Prestasi', 'publikasi' => 'Publikasi', 'rekognisi' => 'Rekognisi'] as $l)<th class="text-center">{{ $l }}</th>@endforeach<th></th></tr></thead>
        @foreach ($dosen as $d)
            @php($rm = $roadmap[$d->id] ?? collect())
            @php($lewat = $rm->filter(fn ($x) => (int) $x->tahun < now()->year || ((int) $x->tahun === now()->year && $x->bulan && $x->bulan < now()->month)))
            <tr>
                <td><a href="{{ route('dashboard.dosen.rapor', $d) }}" class="text-decoration-none fw-semibold">{{ $d->nama }}</a></td>
                @unless ($prodi)<td class="text-muted">{{ $d->program_studi }}</td>@endunless
                <td>@if ($rm->isNotEmpty())
                        <span class="badge text-bg-success">{{ $rm->where('status', 'Tercapai')->count() }} tercapai</span>
                        @if ($n = $lewat->where('status', '!=', 'Tercapai')->count())<span class="badge text-bg-danger">{{ $n }} belum/terlewat</span>@endif
                        <span class="badge text-bg-light">{{ $rm->count() - $lewat->count() }} rencana</span>
                    @else<span class="text-muted">-</span>@endif</td>
                @foreach (['sertifikat', 'prestasi', 'publikasi', 'rekognisi'] as $j)<td class="text-center">{{ $jumlah[$d->id][$j] ?? '·' }}</td>@endforeach
                <td class="text-end"><a href="{{ route('dashboard.dosen.rapor', $d) }}" class="btn btn-sm btn-outline-secondary">Rapor</a></td>
            </tr>
        @endforeach
    </table>
    </div>
</div>
@endsection
