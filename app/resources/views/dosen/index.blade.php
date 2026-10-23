@extends('layout')
@section('title', 'Data Dosen')

@section('actions')
    @include('_tombol-sinkron')
    @can('api', 'dosen.create')
        <a href="{{ route('dosen.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Dosen</a>
    @else
        @include('_terkunci', ['label' => 'Tambah Dosen', 'scope' => 'dosen.create'])
    @endcan
@endsection

@section('content')
<div class="card">
<form class="bar-filter">
    <select name="aktif" class="form-select" onchange="this.form.submit()">
        <option value="1">Aktif</option>
        <option value="0" @selected(request('aktif') === '0')>Nonaktif</option>
    </select>
    <select name="prodi" class="form-select" onchange="this.form.submit()">
        <option value="">Semua prodi</option>
        @foreach (\App\Models\Dosen::PRODI as $p)<option @selected(request('prodi') == $p)>{{ $p }}</option>@endforeach
    </select>
    <select name="jabfung" class="form-select" onchange="this.form.submit()">
        <option value="">Semua jabfung</option>
        @foreach (\App\Models\Dosen::JENJANG as $j)<option @selected(request('jabfung') == $j)>{{ $j }}</option>@endforeach
    </select>
    <div class="input-group">
        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / NIDN">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </div>
    <span class="info">{{ $dosen->total() }} dosen</span>
</form>
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Nama</th><th>NIDN</th><th>Prodi</th><th>Status</th><th>Jabatan Fungsional</th><th>Serdos</th><th></th></tr></thead>
    <tbody>
    @forelse ($dosen as $d)
        <tr>
            <td>
                <a href="{{ route('dosen.show', $d) }}">{{ $d->nama }}</a>
                @if ($d->staff_id)<br><a href="{{ route('staff.show', $d->staff_id) }}" class="small text-decoration-none"><i class="bi bi-person-badge"></i> Pegawai</a>@endif
            </td>
            <td>{{ $d->nidn }}</td>
            <td>{{ $d->program_studi }}</td>
            <td>
                {{ $d->status_dosen }}
                @unless ($d->staff_id)<br><span class="badge text-bg-secondary">Non-staff</span>@endunless
            </td>
            <td>{{ $d->jabatan_fungsional }}@if($d->tmt_jabfung)<br><small class="text-muted">TMT {{ $d->tmt_jabfung->format('d-m-Y') }}</small>@endif</td>
            <td>@if ($d->serdos)<i class="bi bi-check-circle-fill text-success"></i>@endif</td>
            <td class="text-end"><a href="{{ route('dosen.edit', $d) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data dosen.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $dosen->links() }}</div>
@endsection
