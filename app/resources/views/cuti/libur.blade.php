@extends('layout')
@section('title', "Kalender Libur {$tahun}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="d-flex gap-2 align-items-center mb-3">
    <a href="?tahun={{ $tahun - 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <select class="form-select form-select-sm fw-semibold" style="width:auto" onchange="location='?tahun='+this.value" aria-label="Tahun">
        @foreach (range(now()->year - 5, now()->year + 5) as $y)<option @selected($y === $tahun)>{{ $y }}</option>@endforeach
    </select>
    <a href="?tahun={{ $tahun + 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    @if ($tahun !== now()->year)<a href="?" class="btn btn-sm btn-link">Tahun ini</a>@endif
    @if ($libur->isNotEmpty())
        <form method="post" action="{{ route('hari-libur.reset', $tahun) }}" onsubmit="return confirm('Hapus semua {{ $libur->count() }} hari libur tahun {{ $tahun }}?')">@csrf @method('delete')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset {{ $tahun }}</button></form>
    @endif
    <span class="ms-auto small">Cuti bersama memotong jatah cuti tahunan: <strong>{{ $potong }} hari</strong></span>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i> Sesuai SOP: <strong>cuti bersama</strong> (SK Pimpinan) mengurangi jatah cuti tahunan; libur nasional tidak dihitung sebagai hari kerja
    untuk syarat pengajuan 6 hari kerja; cuti tahunan tidak dapat digabung dengan libur yang namanya memuat "Idul Fitri".
</div>

<form method="post" action="{{ route('hari-libur.store') }}" class="card mb-3"><div class="card-body row g-2 align-items-end">
    @csrf
    <div class="col-md-2"><label class="form-label small">Tanggal *</label><input type="date" name="tanggal" id="tglLibur" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label small">Sampai (opsional)</label><input type="date" name="sampai" class="form-control form-control-sm"></div>
    <div class="col-md-4"><label class="form-label small">Keterangan *</label><input name="nama" class="form-control form-control-sm" placeholder="mis. Idul Fitri 1448 H" required></div>
    <div class="col-md-2"><div class="form-check"><input type="checkbox" name="cuti_bersama" value="1" id="cb" class="form-check-input"><label for="cb" class="form-check-label small">Cuti bersama</label></div></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Tambah</button></div>
    @error('tanggal')<div class="text-danger small">{{ $message }}</div>@enderror
</div></form>

<form method="post" action="{{ route('hari-libur.impor') }}" enctype="multipart/form-data" class="card mb-3"><div class="card-body d-flex flex-wrap gap-2 align-items-center">
    @csrf
    <i class="bi bi-file-earmark-excel text-success"></i><span class="small">Impor Excel (Tanggal | Sampai | Keterangan | Cuti bersama):</span>
    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control form-control-sm" style="max-width:280px" required>
    <button class="btn btn-sm btn-success"><i class="bi bi-upload me-1"></i>Impor</button>
    <a href="{{ route('hari-libur.template') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Template + contoh 2026</a>
    <span class="small text-muted">Tanggal yang sudah ada akan ditimpa.</span>
</div></form>

<div class="row g-3">
{{-- Kalender 12 bulan: merah = libur nasional, kuning = cuti bersama, abu = akhir pekan. Klik tanggal → isi form --}}
<div class="col-xl-8">
    @php($peta = $libur->keyBy(fn ($h) => $h->tanggal->toDateString()))
    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xxl-3">
    @foreach (range(1, 12) as $bln)
        @php($awal = \Carbon\Carbon::create($tahun, $bln, 1))
        <div class="col"><div class="card h-100"><div class="card-body p-2">
            <div class="fw-semibold small text-center mb-1">{{ $awal->translatedFormat('F') }}</div>
            <table class="table table-sm table-borderless text-center mb-0 kal">
                <tr class="text-muted">@foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $n)<th class="fw-normal">{{ $n }}</th>@endforeach</tr>
                <tr>
                @for ($i = 1; $i < $awal->dayOfWeekIso; $i++)<td></td>@endfor
                @for ($t = $awal->copy(); $t->month === $bln; $t->addDay())
                    @php($h = $peta[$t->toDateString()] ?? null)
                    <td role="button" title="{{ $h?->nama }}" onclick="document.getElementById('tglLibur').value='{{ $t->toDateString() }}'"
                        class="{{ $h ? ($h->cuti_bersama ? 'bg-warning-subtle fw-bold' : 'bg-danger-subtle text-danger fw-bold') : ($t->isWeekend() ? 'text-danger opacity-50' : '') }} {{ $t->isToday() ? 'border border-primary' : '' }}">{{ $t->day }}</td>
                    @if ($t->dayOfWeekIso === 7 && $t->day < $t->daysInMonth)</tr><tr>@endif
                @endfor
                </tr>
            </table>
        </div></div></div>
    @endforeach
    </div>
    <div class="small text-muted mt-2"><span class="badge bg-danger-subtle text-danger">&nbsp;</span> libur nasional
        <span class="badge bg-warning-subtle ms-2">&nbsp;</span> cuti bersama · klik tanggal untuk mengisi form · arahkan kursor untuk keterangan</div>
</div>
<div class="col-xl-4">
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
</div>
</div>
<style>.kal td,.kal th{padding:2px 0;font-size:.8rem}.kal td[role=button]:hover{outline:1px solid var(--bs-primary)}</style>
@endsection
