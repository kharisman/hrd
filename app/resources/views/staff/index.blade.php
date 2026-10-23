@extends('layout')
@section('title', 'Data Pegawai')

@section('actions')
    @include('_tombol-sinkron')
    @can('api', 'staff.create')
        <a href="{{ route('staff.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Pegawai</a>
    @else
        @include('_terkunci', ['label' => 'Tambah Pegawai', 'scope' => 'staff.create'])
    @endcan
@endsection

@section('content')
<div class="card">
<form class="card-header bg-white row g-2 mx-0 py-3">
    <div class="col-md-3">
        <select name="divisi" class="form-select" onchange="this.form.submit()">
            <option value="">Semua divisi</option>
            @foreach ($divisiList as $id => $nama)<option value="{{ $id }}" @selected(request('divisi') == $id)>{{ $nama }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="level" class="form-select" onchange="this.form.submit()">
            <option value="">Semua level</option>
            @foreach ($levelList as $id => $nama)<option value="{{ $id }}" @selected(request('level') == $id)>{{ $nama }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="atasan" class="form-select" onchange="this.form.submit()">
            <option value="">Semua atasan</option>
            <option value="kosong" @selected(request('atasan') === 'kosong')>Belum ada atasan</option>
            <option value="ada" @selected(request('atasan') === 'ada')>Sudah ada atasan</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="input-group">
            <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / NIP / username">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </div>
    </div>
    <div class="col-md-2 text-md-end align-self-center small text-muted">{{ $staff->total() }} pegawai</div>
</form>

{{-- Atur atasan massal: centang pegawai, pilih atasan, simpan --}}
<form method="post" action="{{ route('staff.atasan') }}" id="massal">
@csrf
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr>
        <th style="width:36px"><input type="checkbox" class="form-check-input" title="Pilih semua di halaman ini"
            onchange="document.querySelectorAll('.pilih').forEach(c => c.checked = this.checked); hitung()"></th>
        <th>NIP</th><th>Nama</th><th>Divisi</th><th>Level</th><th>Atasan</th><th></th>
    </tr></thead>
    <tbody>
    @forelse ($staff as $s)
        <tr>
            <td><input type="checkbox" name="pegawai[]" value="{{ $s->id }}" class="form-check-input pilih" onchange="hitung()"></td>
            <td>{{ $s->nip }}</td>
            <td><a href="{{ route('staff.show', $s) }}">{{ $s->nama }}</a><br><small class="text-muted">{{ $s->username }}</small></td>
            <td>{{ $s->divisi }}</td>
            <td>{{ $s->level }}</td>
            <td>{!! $s->atasan ? e($s->atasan->nama).'<br><small class="text-muted">'.e($s->atasan->level).'</small>' : '<span class="badge text-bg-warning">Belum diatur</span>' !!}</td>
            <td class="text-end"><a href="{{ route('staff.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data. Klik "Sinkron eUniversity".</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2">
    <span class="small text-muted"><span id="jml">0</span> dipilih — atur atasannya ke:</span>
    <select name="atasan_id" class="form-select form-select-sm" style="max-width:360px">
        <option value="">— Kosongkan atasan —</option>
        @foreach ($atasanList as $a)<option value="{{ $a->id }}">{{ $a->nama }} — {{ $a->level }}, {{ $a->divisi }}</option>@endforeach
    </select>
    <button class="btn btn-sm btn-primary" id="simpanMassal" disabled><i class="bi bi-diagram-3 me-1"></i>Simpan Atasan</button>
</div>
</form>
</div>
<div class="mt-3">{{ $staff->links() }}</div>

<script>
    function hitung() {
        const n = document.querySelectorAll('.pilih:checked').length;
        document.getElementById('jml').textContent = n;
        document.getElementById('simpanMassal').disabled = n === 0;
    }
</script>
@endsection
