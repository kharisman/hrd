@extends('layout')
@section('title', 'Dashboard')

@section('actions')
    <a href="{{ route('cuti.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</a>
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

@if ($staff && !$staff->telegram)
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span><i class="bi bi-telegram me-1"></i> Hubungkan Telegram supaya bisa login dengan NIP/NIDN + kode OTP.</span>
    <a href="{{ route('telegram-saya') }}" class="btn btn-sm btn-info">Hubungkan</a>
</div>
@endif

@if ($pengajuanBawahan)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bell me-1"></i> <strong>{{ $pengajuanBawahan }}</strong> pengajuan bawahan (dinas luar, reimburse, dll.) menunggu persetujuan Anda.</span>
    <a href="{{ route('pengajuan.index', ['tab' => 'atasan']) }}" class="btn btn-sm btn-warning">Proses</a>
</div>
@endif

@if ($cutiBawahan->isNotEmpty())
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bell me-1"></i> <strong>{{ $cutiBawahan->count() }}</strong> pengajuan cuti bawahan menunggu persetujuan Anda.</span>
    <a href="{{ route('cuti.index', ['status' => 'Menunggu']) }}" class="btn btn-sm btn-warning">Proses</a>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Jatah Cuti {{ now()->year }}</div>
            <div class="card-body">
                @forelse ($jatah as $r)
                    <div class="batang" style="grid-template-columns: minmax(110px, 40%) 1fr 70px"
                         title="{{ $r->jenis->nama }}: terpakai {{ $r->terpakai }}{{ $r->kuota ? ' dari '.$r->kuota : '' }} hari">
                        <div class="batang-label text-truncate">{{ $r->jenis->nama }}</div>
                        @if ($r->kuota)
                            <div class="batang-jalur"><div class="batang-isi" style="width: {{ $r->sisa / $r->kuota * 100 }}%"></div></div>
                            <div class="batang-nilai"><strong>{{ $r->sisa }}</strong>/{{ $r->kuota }} hari</div>
                        @else
                            <div class="small text-muted">tanpa batas · terpakai {{ $r->terpakai }} hari</div><div></div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted small">Akun belum tertaut ke data pegawai, jatah cuti belum bisa ditampilkan.</div>
                @endforelse
                @if ($jatah->isNotEmpty())<div class="small text-muted mt-2">Batang = sisa jatah. Jatah reset tiap 1 Januari.</div>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span>Cuti Saya</span><a href="{{ route('cuti.index') }}" class="small text-decoration-none">Semua</a></div>
            <div class="list-group list-group-flush">
                @forelse ($cutiSaya as $c)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div>{{ $c->jenisCuti->nama }} <small class="text-muted">· {{ $c->hari }} hari</small></div>
                            <small class="text-muted">{{ $c->tgl_mulai->translatedFormat('d M') }} – {{ $c->tgl_selesai->translatedFormat('d M Y') }}</small>
                        </div>
                        <span class="badge text-bg-{{ ['Menunggu' => 'warning', 'Disetujui' => 'success', 'Ditolak' => 'danger'][$c->status] }}">{{ $c->status }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted small">Belum ada pengajuan cuti.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
