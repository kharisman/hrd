@extends('layout')
@section('title', 'Bot Telegram')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
@if ($pura)
    <div class="alert alert-warning small"><i class="bi bi-cone-striped me-1"></i><strong>Mode uji aktif</strong> (<code>TELEGRAM_PURA=true</code> di .env): pesan tidak dikirim, hanya ditulis ke log aplikasi.</div>
@endif
<div class="row g-3">
    <div class="col-lg-6 d-flex flex-column gap-3">
        <div class="card">
            <div class="card-header">Status Bot</div>
            <div class="card-body">
                @if ($bot)
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        <div><strong>{{ $bot['first_name'] }}</strong> · <a href="https://t.me/{{ $bot['username'] }}" target="_blank" class="text-decoration-none">&#64;{{ $bot['username'] }}</a>
                            <div class="small text-muted">Tersambung · token {{ $tokenSamar }}</div></div>
                    </div>
                @else
                    <div class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>{{ $tokenSamar ? 'Token bot salah atau server Telegram tidak bisa dihubungi.' : 'Token bot belum diatur.' }}</div>
                @endif
            </div>
        </div>

        <form method="post" action="{{ route('pengaturan.telegram.simpan') }}" class="card"><div class="card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label">Ganti Token Bot</label>
                <input name="token" type="password" class="form-control @error('token') is-invalid @enderror" placeholder="Kosongkan bila tidak diganti" autocomplete="off">
                @error('token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Token dari @BotFather. Disimpan terenkripsi. Saat ini memakai bot BAAK.</div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="otp_aktif" value="1" id="otpAktif" @checked($otpAktif)>
                <label class="form-check-label" for="otpAktif">Aktifkan login <strong>NIP/NIDN + OTP Telegram</strong></label>
            </div>
            <button class="btn btn-primary">Simpan</button>
        </div></form>

        <form method="post" action="{{ route('pengaturan.telegram.uji') }}" class="card"><div class="card-body">
            @csrf
            <label class="form-label">Kirim Pesan Uji</label>
            <div class="input-group">
                <input name="chat_id" value="{{ old('chat_id') }}" class="form-control @error('chat_id') is-invalid @enderror" placeholder="Chat ID Telegram (angka)" inputmode="numeric" required>
                <button class="btn btn-outline-primary"><i class="bi bi-send me-1"></i>Kirim</button>
            </div>
            @error('chat_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <div class="form-text">Penerima harus sudah membuka bot dan menekan <em>Start</em>.</div>
        </div></form>
    </div>

    <div class="col-lg-6 d-flex flex-column gap-3">
        <div class="card">
            <div class="card-header">Ringkasan</div>
            <table class="table mb-0">
                @foreach ($stat as $k => $v)<tr><td>{{ $k }}</td><td class="text-end fw-semibold">{{ $v }}</td></tr>@endforeach
            </table>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><span>Pegawai Aktif Belum Punya Telegram</span><span class="badge text-bg-warning">{{ $tanpaTelegram->count() }}</span></div>
            <div class="list-group list-group-flush" style="max-height:260px;overflow:auto">
                @forelse ($tanpaTelegram as $s)
                    <a href="{{ route('staff.show', $s) }}" class="list-group-item list-group-item-action small">{{ $s->nama }} <span class="text-muted">· {{ $s->nip ?? '-' }} · {{ $s->divisi }}</span></a>
                @empty
                    <div class="list-group-item small text-muted">Semua pegawai aktif sudah punya chat ID Telegram.</div>
                @endforelse
            </div>
            <div class="card-footer small text-muted">Isi chat ID di halaman profil pegawai/dosen. Chat ID diambil dari bot seperti @userinfobot.</div>
        </div>
    </div>
</div>
@endsection
