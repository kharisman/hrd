@extends('layout')
@section('title', 'Input Peta Karakter (DISC)')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('input.index') }}" class="text-decoration-none">Input Data</a></li>
@endsection
@php($D = \App\Models\Disc::class)

@section('content')
<form class="bar-filter mb-3">
    <select name="staff" class="form-select" onchange="this.form.submit()">
        <option value="">Pilih pegawai untuk dipetakan / diubah…</option>
        @foreach ($pilihanStaff as $s)<option value="{{ $s->id }}" @selected($edit && $edit->staff_id == $s->id)>{{ $s->nama }}</option>@endforeach
    </select>
    <span class="info">{{ $sudah->count() }} orang sudah dipetakan</span>
</form>

@if ($edit)
    <form method="post" action="{{ route('dashboard.pegawai.disc') }}" class="card mb-3"><div class="card-body">
        @csrf <input type="hidden" name="staff_id" value="{{ $edit->staff_id }}">
        <h6>Peta Karakter · {{ $edit->staff->nama ?? \App\Models\Staff::whereKey($edit->staff_id)->value('nama') }}</h6>
        <div class="row g-2 small">
            <div class="col-md-3"><label class="form-label mb-0">Tim</label><input name="tim" value="{{ $edit->tim }}" list="daftarTim" class="form-control form-control-sm">
                <datalist id="daftarTim">@foreach ($daftarTim as $t)<option>{{ $t }}</option>@endforeach</datalist></div>
            <div class="col-md-2"><label class="form-label mb-0">Profil DISC</label><input name="profil" value="{{ $edit->profil }}" placeholder="mis. S / C-S" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label mb-0">Tanggal nilai</label><input type="date" name="dinilai_at" value="{{ $edit->dinilai_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="form-control form-control-sm"></div>
            @foreach ($D::KOMITMEN + $D::KOMPETENSI as $k => $l)
                <div class="col-6 col-md-1"><label class="form-label mb-0">{{ $l }}</label><input name="{{ $k }}" type="number" min="1" max="4" value="{{ $edit->$k }}" class="form-control form-control-sm"></div>
            @endforeach
        </div>
        <div class="mt-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Simpan</button>
            <a href="{{ route('input.disc') }}" class="btn btn-sm btn-outline-secondary">Batal</a>
            <span class="small text-muted align-self-center">Skor 1–4. 4 pertama = komitmen, 4 berikutnya = kompetensi.</span></div>
    </div></form>
@endif

<div class="card"><ul class="list-group list-group-flush small">
    @forelse ($sudah as $d)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $d->staff->nama ?? '-' }} <span class="text-muted">· {{ $d->tim }} · {{ $d->profil }}</span></span>
            <a href="{{ route('input.disc', ['staff' => $d->staff_id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
        </li>
    @empty
        <li class="list-group-item text-muted">Belum ada pegawai yang dipetakan.</li>
    @endforelse
</ul></div>
@endsection
