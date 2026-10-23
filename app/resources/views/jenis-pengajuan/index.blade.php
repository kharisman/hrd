@extends('layout')
@section('title', 'Jenis Pengajuan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('actions')
    <a href="{{ route('jenis-pengajuan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Jenis</a>
@endsection

@section('content')
<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Nama</th><th>Alur</th><th class="text-center">Isian</th><th class="text-center">Dipakai</th><th class="text-center">Aktif</th><th></th></tr></thead>
    @foreach ($jenis as $kategori => $items)
        <tbody>
            <tr class="table-light"><td colspan="6" class="small fw-semibold text-muted text-uppercase">{{ $kategori }}</td></tr>
            @foreach ($items as $j)
                <tr class="{{ $j->aktif ? '' : 'text-muted' }}">
                    <td>{{ $j->nama }}@if ($j->boleh_anonim) <span class="badge text-bg-light">anonim</span>@endif<br><small class="text-muted">{{ $j->keterangan }}</small></td>
                    <td class="small">{{ \App\Models\JenisPengajuan::ALUR[$j->alur] }}</td>
                    <td class="text-center">{{ count($j->isian) }}</td>
                    <td class="text-center">{{ $j->pengajuan_count }}</td>
                    <td class="text-center">{!! $j->aktif ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle"></i>' !!}</td>
                    <td class="text-end"><a href="{{ route('jenis-pengajuan.edit', $j) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
                </tr>
            @endforeach
        </tbody>
    @endforeach
</table>
</div>
</div>
@endsection
