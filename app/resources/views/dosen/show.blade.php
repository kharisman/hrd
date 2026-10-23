@extends('layout')
@section('title', $dosen->nama)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dosen.index') }}" class="text-decoration-none">Data Dosen</a></li>
@endsection

@section('actions')
    @if ($dosen->staff)<a href="{{ route('staff.show', $dosen->staff) }}" class="btn btn-outline-secondary"><i class="bi bi-person-badge me-1"></i>Profil Pegawai</a>@endif
    <a href="{{ route('dosen.edit', $dosen) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Ubah Data</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Data Dosen</div>
            <table class="table mb-0">
                <tr><th style="width:40%">NIDN</th><td>{{ $dosen->nidn ?: '-' }}</td></tr>
                <tr><th>Username</th><td>{{ $dosen->username ?? '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $dosen->status_dosen ?? '-' }} {!! $dosen->aktif ? '' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td></tr>
                <tr><th>Program Studi</th><td>{{ $dosen->program_studi ?? '-' }}</td></tr>
                <tr><th>Pendidikan Terakhir</th><td>{{ $dosen->pendidikan_terakhir ?? '-' }}</td></tr>
                <tr><th>Jabatan Fungsional</th><td>{{ $dosen->jabatan_fungsional ?? '-' }}{{ $dosen->tmt_jabfung ? ' (TMT '.$dosen->tmt_jabfung->format('d-m-Y').')' : '' }}</td></tr>
                <tr><th>Angka Kredit</th><td>{{ $dosen->angka_kredit ?? '-' }}</td></tr>
                <tr><th>Sertifikasi Dosen</th><td>{{ $dosen->serdos ? 'Sudah' : 'Belum' }}</td></tr>
                <tr><th>Mulai Kerja</th><td>{{ $dosen->mulai_kerja?->format('d-m-Y') ?? '-' }}</td></tr>
                <tr><th>Kontak</th><td>{{ $dosen->no_hp ?? '-' }}{{ $dosen->email ? ' · '.$dosen->email : '' }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6 d-flex flex-column gap-3">
        @include('_telegram', ['model' => $dosen, 'aksi' => route('dosen.telegram', $dosen)])
        @include('riwayat._daftar', ['staffId' => $dosen->staff_id, 'dosenId' => $dosen->id])
    </div>
</div>
@endsection
