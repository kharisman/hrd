<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · HRD ITB PalComTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width:400px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <img src="{{ asset('logo-pct1.png') }}" height="60" alt="ITB PalComTech">
                <h5 class="mt-3 mb-0">Sistem Informasi HRD</h5>
                <small class="text-muted">Masuk dengan akun eUniversity</small>
            </div>
            @if (session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input name="username" class="form-control" value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input name="password" type="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Masuk</button>
                <div class="form-text text-center mt-2">Admin lokal HRD: masuk dengan email.</div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
