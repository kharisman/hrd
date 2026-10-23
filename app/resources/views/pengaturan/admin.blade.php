@extends('layout')
@section('title', 'Admin Lokal')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Admin lokal login dengan <strong>email</strong> dan tetap bisa masuk walaupun server eUniversity tidak bisa diakses.
    Admin sehari-hari sebaiknya memakai akun eUniversity dengan role <code>admin</code>. Password minimal 8 karakter, berisi huruf dan angka.
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Daftar Admin Lokal</div>
            <div class="list-group list-group-flush">
                @foreach ($admins as $a)
                    <div class="list-group-item">
                        <form method="post" action="{{ route('pengaturan.admin.update', $a) }}" class="row g-2 align-items-end">
                            @csrf @method('put')
                            <div class="col-md-3"><label class="form-label small mb-0">Nama</label><input name="name" value="{{ $a->name }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-3"><label class="form-label small mb-0">Email</label><input type="email" name="email" value="{{ $a->email }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-2"><label class="form-label small mb-0">Password baru</label><input type="password" name="password" class="form-control form-control-sm" placeholder="tidak diubah" autocomplete="new-password"></div>
                            <div class="col-md-2"><label class="form-label small mb-0">Ulangi</label><input type="password" name="password_confirmation" class="form-control form-control-sm" autocomplete="new-password"></div>
                            <div class="col-md-2 d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary flex-fill">Simpan</button>
                                @if ($admins->count() > 1 && auth('web')->id() !== $a->id)
                                    <button form="hapus{{ $a->id }}" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </form>
                        <form id="hapus{{ $a->id }}" method="post" action="{{ route('pengaturan.admin.destroy', $a) }}" onsubmit="return confirm('Hapus admin {{ $a->email }}?')">@csrf @method('delete')</form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <form method="post" action="{{ route('pengaturan.admin.store') }}" class="card"><div class="card-body">
            @csrf
            <h6 class="mb-3">Tambah Admin</h6>
            <div class="mb-2"><label class="form-label small mb-0">Nama</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
            <div class="mb-2"><label class="form-label small mb-0">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
            <div class="mb-2"><label class="form-label small mb-0">Password</label><input type="password" name="password" class="form-control" required autocomplete="new-password"></div>
            <div class="mb-3"><label class="form-label small mb-0">Ulangi Password</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
            <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
        </div></form>
    </div>
</div>
@endsection
