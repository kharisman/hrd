@extends('layout')
@section('title', $staff->nama)

@section('breadcrumb')
    @if (Gate::allows('admin'))<li class="breadcrumb-item"><a href="{{ route('staff.index') }}" class="text-decoration-none">Data Pegawai</a></li>@endif
@endsection

@section('actions')
    @if (Gate::allows('admin'))<a href="{{ route('staff.edit', $staff) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Ubah Data</a>@endif
@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Data Pokok <small class="text-muted">(eUniversity)</small></div>
            <table class="table mb-0">
                <tr><th style="width:40%">NIP</th><td>{{ $staff->nip ?? '-' }}</td></tr>
                <tr><th>Username</th><td>{{ $staff->username }}</td></tr>
                <tr><th>Divisi</th><td>{{ $staff->divisi ?? '-' }}</td></tr>
                <tr><th>Level / Jabatan</th><td>{{ $staff->level ?? '-' }}</td></tr>
                <tr><th>Mulai Kerja</th><td>{{ $staff->mulai_kerja?->format('d-m-Y') ?? '-' }}</td></tr>
                <tr><th>TTL</th><td>{{ $staff->tempat_lahir }}{{ $staff->tgl_lahir ? ', '.$staff->tgl_lahir->format('d-m-Y') : '' }}</td></tr>
                <tr><th>Jenis Kelamin</th><td>{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$staff->jenis_kelamin] ?? '-' }}</td></tr>
                <tr><th>Agama / Gol. Darah</th><td>{{ $staff->agama ?? '-' }} / {{ $staff->gol_dar ?? '-' }}</td></tr>
                <tr><th>No. HP</th><td>{{ $staff->no_hp ?? '-' }}</td></tr>
                <tr><th>Alamat</th><td>{{ $staff->alamat ?? '-' }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6 d-flex flex-column gap-3">
        <div class="card">
            <div class="card-header">Kepegawaian <small class="text-muted">(HRD)</small></div>
            <table class="table mb-0">
                <tr><th style="width:40%">Status</th><td>{{ $staff->status_kepegawaian ?? '-' }}</td></tr>
                <tr><th>Pendidikan Terakhir</th><td>{{ $staff->pendidikan_terakhir ?? '-' }}</td></tr>
                <tr><th>Atasan</th><td>
                    @if ($staff->atasan)
                        <a href="{{ Gate::allows('admin') ? route('staff.show', $staff->atasan) : '#' }}" class="text-decoration-none">{{ $staff->atasan->nama }}</a>
                        <br><small class="text-muted">{{ $staff->atasan->level }} · {{ $staff->atasan->divisi }}</small>
                    @else
                        <span class="text-muted">Belum diatur</span>
                    @endif
                </td></tr>
                <tr><th>Bawahan</th><td>{{ $staff->bawahan->pluck('nama')->join(', ') ?: '-' }}</td></tr>
            </table>
        </div>


        @include('riwayat._daftar', ['staffId' => $staff->id])

        @php($ds = $staff->dosen)
        @if ($ds || Gate::allows('admin'))
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Data Dosen
                @if (Gate::allows('admin'))
                    <a href="{{ $ds ? route('dosen.edit', $ds) : route('dosen.create', ['staff_id' => $staff->id]) }}" class="btn btn-sm btn-outline-primary">{{ $ds ? 'Ubah' : 'Daftarkan sebagai Dosen' }}</a>
                @endif
            </div>
            @if ($ds)
            <table class="table mb-0">
                <tr><th style="width:40%">NIDN</th><td>{{ $ds->nidn ?: '-' }}</td></tr>
                <tr><th>Program Studi</th><td>{{ $ds->program_studi ?: '-' }}</td></tr>
                <tr><th>Jabatan Fungsional</th><td>{{ $ds->jabatan_fungsional ?: '-' }}</td></tr>
                <tr><th>Sertifikasi Dosen</th><td>{{ $ds->serdos ? 'Sudah' : 'Belum' }}</td></tr>
            </table>
            @else
            <div class="card-body text-muted small">Pegawai ini belum terdaftar sebagai dosen.</div>
            @endif
        </div>
        @endif
    </div>
    <div class="col-md-6">@include('_jatah-cuti', ['jatah' => $jatah, 'ajukan' => $staff->id == auth()->user()->staff_id])</div>
</div>
@endsection
