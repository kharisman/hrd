@extends('layout')
@section('title', 'Studi Lanjut')

@section('actions')
    <a href="{{ route('riwayat.impor') }}" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Impor Excel</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Pendidikan Terakhir Dosen Aktif</div>
            <table class="table mb-0">
                @php($total = $komposisi->sum())
                @foreach ($komposisi->sortKeys() as $j => $c)
                    <tr><td>{{ $j }}</td><td class="text-end">{{ $c }}</td><td class="text-end text-muted small" style="width:70px">{{ $total ? round($c / $total * 100) : 0 }}%</td></tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="alert alert-info small h-100 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Riwayat pendidikan diisi dari profil <a href="{{ route('staff.index') }}">pegawai</a> atau <a href="{{ route('dosen.index') }}">dosen</a>
            (bagian <strong>Riwayat Pendidikan & Studi Lanjut</strong>). Pendidikan terakhir terisi otomatis dari jenjang tertinggi yang sudah lulus.
            Saat studi lanjut selesai, ubah statusnya menjadi <strong>Lulus</strong> dan unggah ijazahnya.
        </div>
    </div>
</div>

<div class="card">
<form class="bar-filter">
    <select name="status" class="form-select" onchange="this.form.submit()">
        @foreach (\App\Models\RiwayatPendidikan::STATUS as $o)<option @selected(request('status', 'Sedang Studi Lanjut') == $o)>{{ $o }}</option>@endforeach
    </select>
    <select name="jenjang" class="form-select" onchange="this.form.submit()">
        <option value="">Semua jenjang</option>
        @foreach (\App\Models\RiwayatPendidikan::JENJANG as $o)<option @selected(request('jenjang') == $o)>{{ $o }}</option>@endforeach
    </select>
    <select name="pembiayaan" class="form-select" onchange="this.form.submit()">
        <option value="">Semua pembiayaan</option>
        @foreach (\App\Models\RiwayatPendidikan::PEMBIAYAAN as $o)<option @selected(request('pembiayaan') == $o)>{{ $o }}</option>@endforeach
    </select>
    <span class="info">{{ $studi->total() }} data</span>
</form>
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
    <thead><tr><th>Nama</th><th>Jenjang</th><th>Institusi / Prodi</th><th>Masuk</th><th>{{ request('status', 'Sedang Studi Lanjut') === 'Lulus' ? 'Lulus' : 'Target' }}</th><th>Pembiayaan</th><th></th></tr></thead>
    <tbody>
    @forelse ($studi as $r)
        @php($terlambat = $r->status === 'Sedang Studi Lanjut' && $r->tahun_lulus && $r->tahun_lulus < now()->year)
        <tr>
            <td><a href="{{ $r->staff_id ? route('staff.show', $r->staff_id) : route('dosen.show', $r->dosen_id) }}">{{ $r->nama }}</a></td>
            <td><span class="badge text-bg-secondary">{{ $r->jenjang }}</span></td>
            <td>{{ $r->institusi }}@if ($r->program_studi)<br><small class="text-muted">{{ $r->program_studi }}</small>@endif</td>
            <td>{{ $r->tahun_masuk ?? '-' }}</td>
            <td class="{{ $terlambat ? 'text-danger fw-semibold' : '' }}">{{ $r->tahun_lulus ?? '-' }}@if ($terlambat)<br><small>lewat target</small>@endif</td>
            <td>{{ $r->pembiayaan ?? '-' }}</td>
            <td class="text-end"><a href="{{ route('riwayat.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Ubah</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $studi->links() }}</div>
@endsection
