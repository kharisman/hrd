@extends('layout')
@section('title', 'Jenis & Jatah Cuti')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Jatah berlaku <strong>per tahun kalender</strong> (1 Jan – 31 Des) dan <strong>tidak diakumulasi</strong>:
    sisa jatah tahun lalu hangus, dan tiap 1 Januari jatah kembali penuh. Kosongkan jatah untuk jenis yang tidak dibatasi (mis. Izin).
</div>

<div class="card">
<div class="table-responsive">
<table class="table mb-0 align-middle">
    <thead><tr><th>Nama</th><th style="width:130px">Jatah / tahun</th><th>Keterangan</th><th class="text-center">Aktif</th><th class="text-end">Dipakai</th><th></th></tr></thead>
    <tbody>
    @foreach ($jenis as $j)
        {{-- Tiap baris = form sendiri (atribut form=) supaya tabel tetap rapi --}}
        <tr>
            <td><input form="j{{ $j->id }}" name="nama" value="{{ $j->nama }}" class="form-control form-control-sm" required></td>
            <td><div class="input-group input-group-sm"><input form="j{{ $j->id }}" type="number" min="1" name="kuota" value="{{ $j->kuota }}" class="form-control" placeholder="∞"><span class="input-group-text">hari</span></div></td>
            <td><input form="j{{ $j->id }}" name="keterangan" value="{{ $j->keterangan }}" class="form-control form-control-sm"></td>
            <td class="text-center"><input form="j{{ $j->id }}" type="checkbox" name="aktif" value="1" class="form-check-input" @checked($j->aktif)></td>
            <td class="text-end text-muted small">{{ $j->cuti_count }} cuti</td>
            <td class="text-end">
                <form id="j{{ $j->id }}" method="post" action="{{ route('jenis-cuti.update', $j) }}">@csrf @method('put')
                    <button class="btn btn-sm btn-outline-primary">Simpan</button>
                </form>
            </td>
        </tr>
    @endforeach
    <tr class="table-light">
        <td><input form="baru" name="nama" class="form-control form-control-sm" placeholder="Jenis baru" required></td>
        <td><div class="input-group input-group-sm"><input form="baru" type="number" min="1" name="kuota" class="form-control" placeholder="∞"><span class="input-group-text">hari</span></div></td>
        <td><input form="baru" name="keterangan" class="form-control form-control-sm"></td>
        <td class="text-center"><input form="baru" type="checkbox" name="aktif" value="1" class="form-check-input" checked></td>
        <td></td>
        <td class="text-end">
            <form id="baru" method="post" action="{{ route('jenis-cuti.store') }}">@csrf
                <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
            </form>
        </td>
    </tr>
    </tbody>
</table>
</div>
</div>
@endsection
