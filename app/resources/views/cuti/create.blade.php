@extends('layout')
@section('title', 'Ajukan Cuti / Izin')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cuti.index') }}" class="text-decoration-none">Cuti & Izin</a></li>
@endsection

@section('content')
<div class="row g-3">
<div class="col-lg-7">
<form method="post" action="{{ route('cuti.store') }}" class="card"><div class="card-body">
    @csrf
    @if ($pegawai)
        <div class="mb-3"><label class="form-label">Pegawai</label>
            {{-- Ganti pegawai: muat ulang supaya rekap jatahnya tampil --}}
            <select name="staff_id" class="form-select" required onchange="location = '?staff_id=' + this.value">
                <option value="">Pilih pegawai</option>
                @foreach ($pegawai as $p)<option value="{{ $p->id }}" @selected(old('staff_id', $staffId) == $p->id)>{{ $p->nama }} ({{ $p->nip ?? '-' }}, {{ $p->divisi }})</option>@endforeach
            </select></div>
    @endif
    <div class="mb-3"><label class="form-label">Jenis</label>
        <select name="jenis_cuti_id" class="form-select" required>
            @foreach ($rekap as $r)
                <option value="{{ $r->jenis->id }}" @selected(old('jenis_cuti_id') == $r->jenis->id) @disabled($r->sisa === 0)>
                    {{ $r->jenis->nama }}{{ $r->sisa !== null ? " — sisa {$r->sisa} hari" : '' }}
                </option>
            @endforeach
        </select></div>
    <div class="row g-3 mb-3">
        <div class="col"><label class="form-label">Mulai</label><input type="date" name="tgl_mulai" value="{{ old('tgl_mulai') }}" class="form-control" required></div>
        <div class="col"><label class="form-label">Selesai</label><input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}" class="form-control @error('tgl_selesai') is-invalid @enderror" required></div>
    </div>
    <div class="mb-3"><label class="form-label">Alasan</label><textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan') }}</textarea></div>
    <button class="btn btn-primary">Kirim</button>
    <a href="{{ route('cuti.index') }}" class="btn btn-outline-secondary">Batal</a>
</div></form>
</div>

@if ($staffId)
<div class="col-lg-5">
    <div class="card">
        <div class="card-header">Jatah Cuti {{ now()->year }}</div>
        <table class="table mb-0">
            <thead><tr><th>Jenis</th><th class="text-end">Jatah</th><th class="text-end">Terpakai</th><th class="text-end">Sisa</th></tr></thead>
            @foreach ($rekap as $r)
                <tr>
                    <td>{{ $r->jenis->nama }}@if ($r->jenis->keterangan)<br><small class="text-muted">{{ $r->jenis->keterangan }}</small>@endif</td>
                    <td class="text-end">{{ $r->kuota ?? '—' }}</td>
                    <td class="text-end">{{ $r->terpakai }}</td>
                    <td class="text-end fw-semibold {{ $r->sisa === 0 ? 'text-danger' : '' }}">{{ $r->sisa ?? '∞' }}</td>
                </tr>
            @endforeach
        </table>
        <div class="card-footer small text-muted">Jatah berlaku per tahun kalender dan tidak diakumulasi. Terpakai = disetujui + menunggu.</div>
    </div>
</div>
@endif
</div>
@endsection
