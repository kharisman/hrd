<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode OTP · HRD ITB PalComTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width:420px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <i class="bi bi-telegram text-primary" style="font-size:2.6rem"></i>
                <h5 class="mt-2 mb-1">Masukkan Kode OTP</h5>
                <div class="text-muted small">Kode 6 angka telah dikirim ke Telegram <strong>{{ $nama }}</strong>. Berlaku {{ $menit }} menit.</div>
            </div>
            @if (session('ok'))<div class="alert alert-success py-2 small">{{ session('ok') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif

            <form method="post" action="{{ route('login.otp.verifikasi') }}" onsubmit="this.querySelector('button').disabled=true">
                @csrf
                <input name="kode" class="form-control form-control-lg text-center fw-semibold mb-3" style="letter-spacing:.5em"
                       inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" required autofocus
                       oninput="this.value = this.value.replace(/\D/g, ''); if (this.value.length === 6) this.form.requestSubmit()">
                <button class="btn btn-primary w-100">Masuk</button>
            </form>
            <div class="d-flex justify-content-between mt-3 small">
                <a href="{{ route('login') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Ganti NIP/NIDN</a>
                <span class="text-muted">Belum menerima? Tunggu sebentar lalu kirim ulang.</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>
