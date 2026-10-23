@extends('layout')
@section('title', $riwayat->exists ? 'Ubah Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan')
@php
    $pemilik = $riwayat->staff ?? $riwayat->dosen
        ?? \App\Models\Staff::find($riwayat->staff_id) ?? \App\Models\Dosen::find($riwayat->dosen_id);
    $profil = $riwayat->staff_id ? route('staff.show', $riwayat->staff_id) : route('dosen.show', $riwayat->dosen_id);
    $v = fn ($f) => old($f, $riwayat->$f);
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ $profil }}" class="text-decoration-none">{{ $pemilik->nama ?? '-' }}</a></li>
@endsection

@section('content')
<form method="post" enctype="multipart/form-data" action="{{ $riwayat->exists ? route('riwayat.update', $riwayat) : route('riwayat.store') }}" class="card" style="max-width:820px"><div class="card-body">
    @csrf
    @if ($riwayat->exists) @method('put') @endif
    <input type="hidden" name="staff_id" value="{{ $riwayat->staff_id }}">
    <input type="hidden" name="dosen_id" value="{{ $riwayat->dosen_id }}">

    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Jenjang *</label>
            <select name="jenjang" class="form-select" required><option value="">-</option>
                @foreach (\App\Models\RiwayatPendidikan::JENJANG as $o)<option @selected($v('jenjang') == $o)>{{ $o }}</option>@endforeach
            </select></div>
        <div class="col-md-9"><label class="form-label">Institusi / Perguruan Tinggi *</label><input name="institusi" value="{{ $v('institusi') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Program Studi</label><input name="program_studi" value="{{ $v('program_studi') }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Tahun Masuk</label><input type="number" name="tahun_masuk" value="{{ $v('tahun_masuk') }}" class="form-control" min="1950" max="2100"></div>
        <div class="col-md-3"><label class="form-label">Tahun Lulus / Target</label><input type="number" name="tahun_lulus" value="{{ $v('tahun_lulus') }}" class="form-control @error('tahun_lulus') is-invalid @enderror" min="1950" max="2100">@error('tahun_lulus')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Status *</label>
            <select name="status" class="form-select" required>
                @foreach (\App\Models\RiwayatPendidikan::STATUS as $o)<option @selected($v('status') == $o)>{{ $o }}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label">Pembiayaan</label>
            <select name="pembiayaan" class="form-select"><option value="">-</option>
                @foreach (\App\Models\RiwayatPendidikan::PEMBIAYAAN as $o)<option @selected($v('pembiayaan') == $o)>{{ $o }}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label">Berkas Ijazah / SK</label>
            <input type="file" name="berkas" class="form-control @error('berkas') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
            @error('berkas')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">PDF/JPG/PNG, maks. 5 MB.@if ($riwayat->berkas) Sudah ada <a href="{{ route('riwayat.berkas', $riwayat) }}" target="_blank">berkas</a>; unggah untuk mengganti.@endif</div></div>
        <div class="col-12"><label class="form-label">Keterangan</label><textarea name="keterangan" rows="2" class="form-control" placeholder="Mis. nomor SK tugas belajar, judul tesis/disertasi">{{ $v('keterangan') }}</textarea></div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ $profil }}" class="btn btn-outline-secondary">Batal</a>
        @if ($riwayat->exists)<button form="hapus" class="btn btn-outline-danger ms-auto"><i class="bi bi-trash me-1"></i>Hapus</button>@endif
    </div>
</div></form>
@if ($riwayat->exists)
<form id="hapus" method="post" action="{{ route('riwayat.destroy', $riwayat) }}" onsubmit="return confirm('Hapus riwayat pendidikan ini?')">@csrf @method('delete')</form>
@endif
@endsection
