@extends('layout')
@section('title', 'Dashboard')

@section('actions')
    <a href="{{ route('pengajuan.create', ['jenis' => $jenisCuti]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</a>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <div class="ikon flex-shrink-0" style="width:52px;height:52px;border-radius:50%;display:grid;place-items:center;background:#e7f0ff;color:#0d6efd;font-size:1.5rem"><i class="bi bi-person"></i></div>
        <div class="flex-grow-1">
            <div class="fs-5 fw-semibold">Halo, {{ $akun->name }}</div>
            <div class="text-muted small">
                @if ($staff)
                    {{ $staff->level }} · {{ $staff->divisi }} · Atasan: {{ $staff->atasan->nama ?? 'belum diatur' }}
                @else
                    {{ ['dosen' => 'Dosen', 'staff' => 'Staf'][$akun->peran] ?? '' }} · akun belum tertaut ke data pegawai
                @endif
            </div>
        </div>
        @if ($staff)<a href="{{ route('staff.show', $staff) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-vcard me-1"></i>Profil Saya</a>@endif
    </div>
</div>

@if ($pengajuanBawahan)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bell me-1"></i> <strong>{{ $pengajuanBawahan }}</strong> pengajuan (cuti, dinas luar, reimburse, dll.) menunggu persetujuan Anda.</span>
    <a href="{{ route('pengajuan.index', ['tab' => 'tugas']) }}" class="btn btn-sm btn-warning">Proses</a>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        @include('_jatah-cuti', ['jatah' => $jatah, 'ajukan' => true])
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Cuti Saya</span><a href="{{ route('pengajuan.index') }}" class="small text-decoration-none">Semua</a></div>
            <div class="list-group list-group-flush">
                @forelse ($cutiSaya as $c)
                    <a href="{{ route('pengajuan.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div>{{ $c->jenisCuti->nama }} <small class="text-muted">· {{ $c->hari }} hari</small></div>
                            <small class="text-muted">{{ $c->tgl_mulai->translatedFormat('d M') }} – {{ $c->tgl_selesai->translatedFormat('d M Y') }}</small>
                        </div>
                        <span class="badge text-bg-{{ \App\Models\Pengajuan::warna($c->status) }}">{{ $c->status }}</span>
                    </a>
                @empty
                    <div class="list-group-item text-muted small">Belum ada pengajuan cuti.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
