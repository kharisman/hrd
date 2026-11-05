@extends('layout')
@section('title', "Kalender Libur {$tahun}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="d-flex gap-2 align-items-center mb-3">
    <a href="?tahun={{ $tahun - 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <strong>{{ $tahun }}</strong>
    <a href="?tahun={{ $tahun + 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    <span class="ms-auto small">Cuti bersama memotong jatah cuti tahunan: <strong>{{ $potong }} hari</strong></span>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i> Sesuai SOP: <strong>cuti bersama</strong> (SK Pimpinan) mengurangi jatah cuti tahunan; libur nasional tidak dihitung sebagai hari kerja
    untuk syarat pengajuan 6 hari kerja; cuti tahunan tidak dapat digabung dengan libur yang namanya memuat "Idul Fitri".
</div>

<form method="post" action="{{ route('hari-libur.store') }}" class="card mb-3"><div class="card-body row g-2 align-items-end">
    @csrf
    <div class="col-md-2"><label class="form-label small">Tanggal *</label><input type="date" name="tanggal" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label small">Sampai (opsional)</label><input type="date" name="sampai" class="form-control form-control-sm"></div>
    <div class="col-md-4"><label class="form-label small">Keterangan *</label><input name="nama" class="form-control form-control-sm" placeholder="mis. Idul Fitri 1448 H" required></div>
    <div class="col-md-2"><div class="form-check"><input type="checkbox" name="cuti_bersama" value="1" id="cb" class="form-check-input"><label for="cb" class="form-check-label small">Cuti bersama</label></div></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Tambah</button></div>
    @error('tanggal')<div class="text-danger small">{{ $message }}</div>@enderror
</div></form>

<div class="card"><div class="table-responsive">
<table class="table mb-0 align-middle">
    <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Jenis</th><th></th></tr></thead>
    @forelse ($libur as $h)
        <tr class="{{ $h->tanggal->isWeekend() ? 'text-muted' : '' }}">
            <td>{{ $h->tanggal->translatedFormat('D, d M Y') }}</td>
            <td>{{ $h->nama }}</td>
            <td>@if ($h->cuti_bersama)<span class="badge text-bg-warning">Cuti bersama</span>@else<span class="badge text-bg-danger">Libur nasional</span>@endif</td>
            <td class="text-end"><form method="post" action="{{ route('hari-libur.destroy', $h) }}" onsubmit="return confirm('Hapus?')">@csrf @method('delete')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-muted text-center">Belum ada hari libur di {{ $tahun }}.</td></tr>
    @endforelse
</table>
</div></div>
@endsection
