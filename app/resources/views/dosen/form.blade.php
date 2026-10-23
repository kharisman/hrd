@extends('layout')
@section('title', $dosen->exists ? 'Ubah Dosen' : 'Tambah Dosen')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dosen.index') }}" class="text-decoration-none">Data Dosen</a></li>
@endsection

@php
    $v = fn ($f) => old($f, $dosen->$f instanceof \Carbon\Carbon ? $dosen->$f->format('Y-m-d') : $dosen->$f);
    $sel = fn ($f, $opts) => collect($opts)->map(fn ($o) => '<option'.($v($f) == $o ? ' selected' : '').'>'.e($o).'</option>')->prepend('<option value="">-</option>')->join('');
@endphp

@section('content')
<form method="post" action="{{ $dosen->exists ? route('dosen.update', $dosen) : route('dosen.store') }}" class="card"><div class="card-body">
    @csrf
    @if ($dosen->exists) @method('put') @endif

    @include('_biodata', ['m' => $dosen])

    <h6 class="mt-4 mb-3 border-bottom pb-2">Data Dosen</h6>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">NIDN / NUPTK</label><input name="nidn" value="{{ $v('nidn') }}" class="form-control @error('nidn') is-invalid @enderror" placeholder="Kosong jika belum ada">@error('nidn')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3"><label class="form-label">Status Dosen</label><select name="status_dosen" class="form-select">{!! $sel('status_dosen', \App\Models\Dosen::STATUS) !!}</select></div>
        <div class="col-md-3"><label class="form-label">Program Studi</label><select name="program_studi" class="form-select">{!! $sel('program_studi', \App\Models\Dosen::PRODI) !!}</select></div>
        <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ $v('email') }}" class="form-control"></div>
        <div class="col-12">
            <label class="form-label">Data Pegawai</label>
            <select name="staff_id" class="form-select">
                <option value="">— Bukan pegawai (dosen LB / luar) —</option>
                @foreach ($pegawai as $p)<option value="{{ $p->id }}" @selected($v('staff_id') == $p->id)>{{ $p->nama }} ({{ $p->nip ?? '-' }}, {{ $p->divisi }})</option>@endforeach
            </select>
            <div class="form-text">Pilih jika dosen ini juga pegawai/staff ITB PalComTech.</div>
        </div>
    </div>

    <h6 class="mt-4 mb-3 border-bottom pb-2">Jenjang Akademik <small class="text-muted fw-normal">(hanya di HRD)</small></h6>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Pendidikan Terakhir</label><select name="pendidikan_terakhir" class="form-select">{!! $sel('pendidikan_terakhir', ['S1', 'S2', 'S3']) !!}</select></div>
        <div class="col-md-3"><label class="form-label">Jabatan Fungsional</label><select name="jabatan_fungsional" class="form-select">{!! $sel('jabatan_fungsional', \App\Models\Dosen::JENJANG) !!}</select></div>
        <div class="col-md-3"><label class="form-label">TMT Jabfung</label><input type="date" name="tmt_jabfung" value="{{ $v('tmt_jabfung') }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Angka Kredit</label><input type="number" min="0" name="angka_kredit" value="{{ $v('angka_kredit') }}" class="form-control"></div>
    </div>
    <div class="d-flex gap-4 mt-3">
        <div class="form-check"><input type="checkbox" name="serdos" value="1" id="serdos" class="form-check-input" @checked($v('serdos'))><label for="serdos" class="form-check-label">Sudah Sertifikasi Dosen</label></div>
        <div class="form-check"><input type="checkbox" name="aktif" value="1" id="aktif" class="form-check-input" @checked($v('aktif'))><label for="aktif" class="form-check-label">Aktif</label></div>
    </div>

    <div class="mt-4 d-flex gap-2">
        @php($scopeSimpan = $dosen->api_id ? 'dosen.update' : 'dosen.create')
        @can('api', $scopeSimpan)
            <button class="btn btn-primary">Simpan</button>
        @else
            @include('_terkunci', ['label' => 'Simpan', 'scope' => $scopeSimpan, 'kelas' => 'btn-secondary'])
        @endcan
        <a href="{{ route('dosen.index') }}" class="btn btn-outline-secondary">Batal</a>
        @if ($dosen->exists)
            <span class="ms-auto">
                @can('api', 'dosen.delete')
                    <button form="hapus" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                @else
                    @include('_terkunci', ['label' => 'Hapus', 'scope' => 'dosen.delete'])
                @endcan
            </span>
        @endif
    </div>
</div></form>
@if ($dosen->exists)
<form id="hapus" method="post" action="{{ route('dosen.destroy', $dosen) }}" onsubmit="return confirm('Hapus {{ e($dosen->nama) }} dari eUniversity dan HRD?')">@csrf @method('delete')</form>
@endif
@endsection
