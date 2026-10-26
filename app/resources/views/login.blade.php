<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · HRD ITB PalComTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
@php
    $tab = session('tab') === 'otp' || old('identitas') ? 'otp' : 'eu';
@endphp
<div class="container" style="max-width:420px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <img src="{{ asset('logo-pct1.png') }}" height="60" alt="ITB PalComTech">
                <h5 class="mt-3 mb-0">Sistem Informasi HRD</h5>
            </div>
            @if (session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

            <ul class="nav nav-pills nav-fill mb-3 small" role="tablist">
                <li class="nav-item"><button class="nav-link {{ $tab === 'eu' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tabEu" type="button" role="tab"><i class="bi bi-person-lock me-1"></i>Akun eUniversity</button></li>
                <li class="nav-item"><button class="nav-link {{ $tab === 'otp' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tabOtp" type="button" role="tab"><i class="bi bi-telegram me-1"></i>NIP / NIDN</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $tab === 'eu' ? 'show active' : '' }}" id="tabEu" role="tabpanel">
                    <form method="post" action="{{ route('login') }}" onsubmit="sibuk(this)">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Username eUniversity</label>
                            <input name="username" class="form-control" value="{{ old('username') }}" required @if ($tab === 'eu') autofocus @endif autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" class="form-control" required autocomplete="current-password">
                        </div>
                        <button class="btn btn-primary w-100">Masuk</button>
                    </form>
                </div>

                <div class="tab-pane fade {{ $tab === 'otp' ? 'show active' : '' }}" id="tabOtp" role="tabpanel">
                    <form method="post" action="{{ route('login.otp.kirim') }}" onsubmit="sibuk(this)">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">NIP / NIDN / username</label>
                            <input name="identitas" class="form-control" value="{{ old('identitas') }}" required @if ($tab === 'otp') autofocus @endif inputmode="text" placeholder="mis. 13.PCT.07">
                            <div class="form-text"><i class="bi bi-telegram me-1"></i>Kode OTP dikirim bot eUniversity ke Telegram Anda. Pastikan sudah menekan Start di bot tersebut.</div>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Kirim Kode OTP</button>
                    </form>
                    @if (session('kandidat'))
                        <div class="mt-3 small text-muted">Pilih username Anda:</div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @foreach (session('kandidat') as $u)
                                <form method="post" action="{{ route('login.otp.kirim') }}" onsubmit="sibuk(this)">@csrf
                                    <input type="hidden" name="identitas" value="{{ $u }}"><button class="btn btn-sm btn-outline-primary">{{ $u }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Tombol jadi spinner & tidak bisa diklik dua kali (login bisa beberapa detik karena menghubungi eUniversity/Telegram)
    function sibuk(f) { const b = f.querySelector('button'); b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...'; }
</script>
</body>
</html>
