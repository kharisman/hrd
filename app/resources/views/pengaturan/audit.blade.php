@extends('layout')
@section('title', 'Audit Log')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<form class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label small">Cari (nama / aksi / URL)</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small">Dari</label><input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small">Sampai</label><input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm"></div>
    <div class="col-md-3"><button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Tampilkan</button>
        <a href="?" class="btn btn-sm btn-link">Reset</a></div>
</div></form>

<div class="card"><div class="table-responsive">
<table class="table table-sm mb-0 align-middle small">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>URL</th><th>Data</th><th>IP</th></tr></thead>
    @forelse ($log as $l)
        <tr>
            <td class="text-nowrap">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
            <td>{{ $l->nama ?? '-' }}</td>
            <td><span class="badge {{ $l->metode === 'GET' ? 'text-bg-light border' : ($l->metode === 'DELETE' ? 'text-bg-danger' : 'text-bg-primary') }}">{{ $l->metode }}</span> {{ $l->aksi }}</td>
            <td class="text-break" style="max-width:280px">{{ parse_url($l->url, PHP_URL_PATH) }}</td>
            <td style="max-width:320px">@if ($l->data)<details><summary class="text-primary">lihat</summary><pre class="small mb-0 text-wrap">{{ json_encode($l->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</td>
            <td class="text-muted">{{ $l->ip }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted">Belum ada catatan.</td></tr>
    @endforelse
</table>
</div></div>
<div class="mt-2">{{ $log->links() }}</div>
@endsection
