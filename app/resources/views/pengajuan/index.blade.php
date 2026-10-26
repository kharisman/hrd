@extends('layout')
@section('title', 'Pengajuan')

@section('actions')
    <a href="{{ route('pengajuan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Pengajuan</a>
@endsection

@section('content')
<ul class="nav nav-tabs mb-0">
    @if ($bisaMenyetujui)
        <li class="nav-item"><a class="nav-link {{ $tab === 'tugas' ? 'active' : '' }}" href="?tab=tugas">Perlu Persetujuan Saya
            @if ($jumlahTugas)<span class="badge text-bg-warning ms-1">{{ $jumlahTugas }}</span>@endif</a></li>
    @endif
    <li class="nav-item"><a class="nav-link {{ $tab === 'saya' ? 'active' : '' }}" href="?tab=saya">Pengajuan Saya</a></li>
    @if ($punyaBawahan)
        <li class="nav-item"><a class="nav-link {{ $tab === 'atasan' ? 'active' : '' }}" href="?tab=atasan">Pengajuan Bawahan</a></li>
    @endif
    @can('admin')
        <li class="nav-item"><a class="nav-link {{ $tab === 'semua' ? 'active' : '' }}" href="?tab=semua">Semua (HRD)</a></li>
    @endcan
</ul>

<div class="card border-top-0 rounded-top-0">
    @if (in_array($tab, ['atasan', 'semua']))
    <form class="bar-filter rounded-0">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (\App\Models\Pengajuan::semuaStatus() as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
        </select>
        @if ($tab === 'semua')
        <select name="jenis" class="form-select" style="max-width:280px" onchange="this.form.submit()">
            <option value="">Semua jenis</option>
            @foreach ($jenisList as $id => $nama)<option value="{{ $id }}" @selected(request('jenis') == $id)>{{ $nama }}</option>@endforeach
        </select>
        @endif
        <span class="info">{{ $pengajuan->total() }} pengajuan</span>
    </form>
    @endif
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Tanggal</th><th>Jenis</th>@if ($tab !== 'saya')<th>Pengaju</th>@endif<th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($pengajuan as $p)
            <tr>
                <td class="text-nowrap">{{ $p->created_at->translatedFormat('d M Y') }}<br><small class="text-muted">{{ $p->created_at->format('H:i') }}</small></td>
                <td>{{ $p->jenis->nama }}<br><small class="text-muted">{{ $p->jenis->kategori }}</small></td>
                @if ($tab !== 'saya')
                    <td>{{ $p->namaPengaju($me) }}@unless ($p->anonim)<br><small class="text-muted">{{ $p->staff->divisi ?? '' }}</small>@endunless</td>
                @endif
                <td><span class="badge text-bg-{{ \App\Models\Pengajuan::warna($p->status) }}">{{ $p->status }}</span></td>
                <td class="text-end"><a href="{{ route('pengajuan.show', $p) }}" class="btn btn-sm btn-outline-secondary">{{ $tab === 'tugas' ? 'Proses' : 'Lihat' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">
                {{ ['saya' => 'Belum ada pengajuan. Klik "Buat Pengajuan".', 'tugas' => 'Tidak ada pengajuan yang menunggu keputusan Anda.'][$tab] ?? 'Tidak ada pengajuan.' }}
            </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-3">{{ $pengajuan->links() }}</div>
@endsection
