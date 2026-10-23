@extends('layout')
@section('title', 'Telegram Saya')

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        @if (!$bisa)
            <div class="alert alert-warning">Akun Anda belum tertaut ke data pegawai/dosen di HRD, jadi Telegram belum bisa dihubungkan. Hubungi bagian SDM.</div>
        @elseif ($terhubung)
            {{-- Sudah terhubung --}}
            <div class="card border-success">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:2.4rem"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Telegram terhubung</div>
                        <div class="text-muted small">Chat ID {{ substr($terhubung, 0, 3) . str_repeat('•', max(0, strlen($terhubung) - 5)) . substr($terhubung, -2) }}.
                            Anda bisa login dengan <strong>NIP/NIDN + kode OTP</strong> yang dikirim ke Telegram ini.</div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex flex-wrap gap-2">
                    <form method="post" action="{{ route('telegram-saya.putus') }}" onsubmit="return confirm('Putuskan Telegram? Anda tidak bisa login dengan NIP/NIDN + OTP sampai menghubungkannya lagi.')">
                        @csrf <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Putuskan</button>
                    </form>
                    <span class="small text-muted align-self-center">Ganti akun Telegram? Putuskan dulu, lalu hubungkan yang baru.</span>
                </div>
            </div>
        @elseif ($menunggu)
            {{-- Langkah 2: masukkan kode verifikasi --}}
            <div class="card">
                <div class="card-header"><strong>Langkah 2</strong> · Masukkan kode verifikasi</div>
                <div class="card-body">
                    <p class="small text-muted">Kode 6 angka dikirim ke chat ID <strong>{{ $menunggu['chat_id'] }}</strong>. Berlaku 10 menit.</p>
                    <form method="post" action="{{ route('telegram-saya.verifikasi') }}" class="d-flex gap-2" style="max-width:360px">
                        @csrf
                        <input name="kode" class="form-control text-center fw-semibold" style="letter-spacing:.4em" inputmode="numeric" pattern="\d{6}" maxlength="6"
                               autocomplete="one-time-code" required autofocus oninput="this.value = this.value.replace(/\D/g, '')">
                        <button class="btn btn-primary text-nowrap">Hubungkan</button>
                    </form>
                    <form method="post" action="{{ route('telegram-saya.batal') }}" class="mt-2">@csrf <button class="btn btn-link btn-sm p-0">Salah chat ID? Ulangi</button></form>
                </div>
            </div>
        @else
            {{-- Langkah 1: masukkan chat ID --}}
            <div class="card">
                <div class="card-header"><strong>Hubungkan Telegram</strong> · supaya bisa login dengan NIP/NIDN + OTP</div>
                <div class="card-body">
                    <ol class="mb-3">
                        <li class="mb-2">Buka bot HRD
                            @if ($bot)<a href="https://t.me/{{ $bot }}" target="_blank" class="fw-semibold">&#64;{{ $bot }}</a>@else (hubungi SDM) @endif
                            di Telegram, lalu tekan <strong>Start</strong>.</li>
                        <li class="mb-2">Cari tahu <strong>chat ID</strong> Anda: buka <a href="https://t.me/userinfobot" target="_blank">&#64;userinfobot</a>, tekan Start, lalu salin angka pada <em>Id</em>.</li>
                        <li>Tempelkan chat ID di bawah. Kami akan mengirim kode verifikasi ke Telegram Anda.</li>
                    </ol>
                    <form method="post" action="{{ route('telegram-saya.kirim') }}" class="d-flex gap-2" style="max-width:420px">
                        @csrf
                        <input name="chat_id" value="{{ old('chat_id') }}" class="form-control" placeholder="Chat ID, mis. 123456789" inputmode="numeric" required>
                        <button class="btn btn-primary text-nowrap"><i class="bi bi-send me-1"></i>Kirim Kode</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-lock me-1"></i>Kenapa perlu kode verifikasi?</div>
            <div class="card-body small text-muted">
                Kode dikirim ke chat ID yang Anda masukkan untuk memastikan Telegram itu benar milik Anda. Dengan begitu tidak ada yang bisa
                mendaftarkan Telegram orang lain ke akun Anda, atau sebaliknya. Kode OTP login nantinya hanya dikirim ke Telegram yang sudah terverifikasi.
            </div>
        </div>
    </div>
</div>
@endsection
