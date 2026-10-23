@extends('layout')
@section('title', 'Cuti & Izin')

@section('actions')
        <a href="{{ route('cuti.create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</a>
@endsection

@section('content')
<div class="card">
<form class="card-header bg-white row g-2 mx-0 py-3">
    <div class="col-md-4">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (['Menunggu', 'Disetujui', 'Ditolak'] as $s)<option @selected(request('status') == $s)>{{ $s }}</option>@endforeach
        </select>
    </div>
</form>
<div class="table-responsive">
<table class="table mb-0 align-middle">
    <thead><tr><th>Pegawai</th><th>Jenis</th><th>Tanggal</th><th>Alasan</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($cuti as $c)
        <tr>
            <td>{{ $c->staff->nama ?? '#'.$c->staff_id }}</td>
            <td>{{ $c->jenisCuti->nama }}</td>
            <td class="text-nowrap">{{ $c->tgl_mulai->format('d/m/Y') }} – {{ $c->tgl_selesai->format('d/m/Y') }}<br><small class="text-muted">{{ $c->hari }} hari</small></td>
            <td>{{ $c->alasan }}</td>
            <td>
                <span class="badge text-bg-{{ ['Menunggu' => 'warning', 'Disetujui' => 'success', 'Ditolak' => 'danger'][$c->status] }}">{{ $c->status }}</span>
                @if ($c->diproses_oleh)<br><small class="text-muted">oleh {{ $c->diproses_oleh }}</small>@endif
                @if ($c->catatan)<br><small class="text-muted">{{ $c->catatan }}</small>@endif
            </td>
            <td class="text-end">
                {{-- Admin atau atasan langsung (bukan pengaju sendiri) --}}
                @if ($c->status === 'Menunggu' && (Gate::allows('admin') || ($me && $c->staff_id != $me)))
                    <form method="post" action="{{ route('cuti.proses', $c) }}" class="d-flex gap-1 justify-content-end">
                        @csrf
                        <input name="catatan" class="form-control form-control-sm" placeholder="Catatan" style="width:120px">
                        <button name="status" value="Disetujui" class="btn btn-sm btn-success">Setujui</button>
                        <button name="status" value="Ditolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $cuti->links() }}</div>
@endsection
