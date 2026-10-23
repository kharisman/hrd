@extends('layout')
@section('title', 'Integrasi API')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('actions')
    @can('api', 'staff.read')
        <form method="post" action="{{ route('pengaturan.api.cek') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<span class=&quot;spinner-border spinner-border-sm me-1&quot;></span>Menghubungi server...'">
            @csrf
            <button class="btn btn-primary"><i class="bi bi-plug me-1"></i>Cek Koneksi</button>
        </form>
    @else
        @include('_terkunci', ['label' => 'Cek Koneksi', 'scope' => 'staff.read'])
    @endcan
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">Koneksi</div>
            <table class="table mb-0">
                <tr><th style="width:40%">Alamat API</th><td><code>{{ $url }}</code></td></tr>
                <tr><th>API Key</th><td>{!! $adaKey ? '<span class="badge text-bg-success">Terpasang</span>' : '<span class="badge text-bg-danger">Belum diisi</span>' !!}</td></tr>
            </table>
            <div class="card-footer small text-muted">Diatur di file <code>.env</code> server (<code>PCT_API_URL</code>, <code>PCT_API_KEY</code>), bukan dari layar ini, supaya rahasia tidak tersimpan di database.</div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Akun yang Dipakai</div>
            <div class="card-body small">
                Semua aksi ke eUniversity (sinkron, tambah/ubah/hapus dosen & pegawai) memakai akun yang sedang login:
                <strong>{{ $akun->name }}</strong> (<code>{{ $akun->username }}</code>). Tombol aktif sesuai izin (scope) akun ini.
                @unless ($adaToken)
                    <div class="text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Sesi eUniversity tidak ditemukan, silakan keluar lalu login ulang.</div>
                @endunless
            </div>
            <table class="table table-sm mb-0">
                @foreach ($izin as $aksi => [$scope, $boleh])
                    <tr>
                        <td>{{ $aksi }}</td>
                        <td>@foreach ($scope as $s)<code class="me-1">{{ $s }}</code>@endforeach</td>
                        <td class="text-end">{!! $boleh ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Boleh</span>' : '<span class="text-muted"><i class="bi bi-lock"></i> Terkunci</span>' !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="card">
            <div class="card-header">Pemetaan Role eUniversity → Peran HRD</div>
            <table class="table mb-0">
                @foreach ($peran as $hrd => $roles)
                    <tr><th style="width:40%">{{ ['admin' => 'Admin HRD', 'dosen' => 'Dosen', 'staff' => 'Staf'][$hrd] ?? $hrd }}</th>
                        <td>@foreach ($roles as $r)<code class="me-1">{{ $r }}</code>@endforeach</td></tr>
                @endforeach
                <tr><th>Ditolak</th><td class="text-muted small">role lain (mis. <code>mahasiswa</code>, <code>svc:*</code>)</td></tr>
            </table>
            <div class="card-footer small text-muted">Diatur di <code>config/services.php</code> → <code>pct.peran</code>.</div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Login eUniversity Terakhir</div>
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Akun</th><th>Peran</th><th>Tertaut</th><th>Login</th></tr></thead>
                @forelse ($akunLogin as $a)
                    <tr>
                        <td>{{ $a->name }}<br><small class="text-muted">{{ $a->username }}</small></td>
                        <td>{{ ['admin' => 'Admin', 'dosen' => 'Dosen', 'staff' => 'Staf'][$a->peran] ?? $a->peran }}</td>
                        <td class="small">{!! $a->staff_id ? '<i class="bi bi-check text-success"></i> pegawai' : '' !!} {!! $a->dosen_id ? '<i class="bi bi-check text-success"></i> dosen' : '' !!}
                            @unless ($a->staff_id || $a->dosen_id)<span class="text-warning">belum</span>@endunless</td>
                        <td class="small text-muted">{{ $a->last_login_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted small">Belum ada yang login lewat eUniversity.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
