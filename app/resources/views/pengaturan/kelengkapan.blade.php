@extends('layout')
@section('title', 'Kelengkapan Data Pegawai')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-calendar-x me-1"></i>Tanpa tanggal mulai kerja</span>
                <span class="badge {{ $tanpaMulai->isEmpty() ? 'text-bg-success' : 'text-bg-warning' }}">{{ $tanpaMulai->count() }} / {{ $total }}</span></div>
            <div class="card-body small text-muted pb-0">Tanpa tanggal ini syarat cuti tahunan "sudah bekerja 12 bulan" tidak bisa dicek (dilewati).</div>
            <ul class="list-group list-group-flush small">
                @forelse ($tanpaMulai as $s)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $s->nama }} <span class="text-muted">· {{ $s->divisi }}</span></span>
                        <a href="{{ route('staff.edit', $s) }}" class="text-decoration-none">Lengkapi</a></li>
                @empty
                    <li class="list-group-item text-success">Semua pegawai aktif sudah lengkap.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-telegram me-1"></i>Telegram belum terhubung</span>
                @if ($tanpaTelegram !== null)<span class="badge {{ $tanpaTelegram->isEmpty() ? 'text-bg-success' : 'text-bg-warning' }}">{{ $tanpaTelegram->count() }} / {{ $total }}</span>@endif</div>
            <div class="card-body small text-muted pb-0">Tidak menerima notifikasi pengajuan. Minta pegawai menekan <strong>Start</strong> di bot eUniversity, lalu hubungi admin API. Data diperbarui tiap 6 jam.</div>
            <ul class="list-group list-group-flush small">
                @if ($tanpaTelegram === null)
                    <li class="list-group-item text-danger">API eUniversity tidak dapat dihubungi, coba lagi nanti.</li>
                @else
                    @forelse ($tanpaTelegram as $s)
                        <li class="list-group-item">{{ $s->nama }} <span class="text-muted">· {{ $s->username ?: 'tanpa username' }}</span></li>
                    @empty
                        <li class="list-group-item text-success">Semua pegawai aktif sudah terhubung.</li>
                    @endforelse
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
