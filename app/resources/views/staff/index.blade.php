@extends('layout')
@section('title', 'Data Pegawai')

@section('actions')
    <a href="{{ route('staff.atasan') }}" class="btn btn-outline-primary"><i class="bi bi-diagram-3 me-1"></i>Atasan & Bawahan</a>
    @include('_tombol-sinkron')
    @can('api', 'staff.create')
        <a href="{{ route('staff.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Pegawai</a>
    @else
        @include('_terkunci', ['label' => 'Tambah Pegawai', 'scope' => 'staff.create'])
    @endcan
@endsection

@section('content')
<div class="card">
<form class="bar-filter">
    <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="aktif">Aktif</option>
        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        <option value="semua" @selected(request('status') === 'semua')>Aktif & nonaktif</option>
    </select>
    <select name="divisi" class="form-select" onchange="this.form.submit()">
        <option value="">Semua divisi</option>
        @foreach ($divisiList as $id => $nama)<option value="{{ $id }}" @selected(request('divisi') == $id)>{{ $nama }}</option>@endforeach
    </select>
    <select name="level" class="form-select" onchange="this.form.submit()">
        <option value="">Semua level</option>
        @foreach ($levelList as $id => $nama)<option value="{{ $id }}" @selected(request('level') == $id)>{{ $nama }}</option>@endforeach
    </select>
    <select name="atasan" class="form-select" onchange="this.form.submit()">
        <option value="">Semua atasan</option>
        <option value="kosong" @selected(request('atasan') === 'kosong')>Belum ada atasan</option>
        <option value="ada" @selected(request('atasan') === 'ada')>Sudah ada atasan</option>
    </select>
    <div class="input-group">
        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / NIP">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </div>
    <span class="info">{{ $staff->total() }} pegawai</span>
</form>
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>NIP</th><th>Nama</th><th>Divisi</th><th>Level</th><th>Atasan</th><th></th></tr></thead>
    <tbody>
    @forelse ($staff as $s)
        <tr>
            <td>{{ $s->nip }}</td>
            <td><a href="{{ route('staff.show', $s) }}">{{ $s->nama }}</a>@unless ($s->aktif) <span class="badge text-bg-secondary">Nonaktif</span>@endunless<br><small class="text-muted">{{ $s->username }}</small></td>
            <td>{{ $s->divisi }}</td>
            <td>{{ $s->level }}</td>
            <td>{!! $s->atasan ? e($s->atasan->nama).'<br><small class="text-muted">'.e($s->atasan->level).'</small>' : '<span class="badge text-bg-warning">Belum diatur</span>' !!}</td>
            <td class="text-end"><a href="{{ route('staff.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data. Klik "Sinkron eUniversity".</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
<div class="mt-3">{{ $staff->links() }}</div>
@endsection
