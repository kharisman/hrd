@extends('layout')
@section('title', $p->jenis->nama)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengajuan.index') }}" class="text-decoration-none">Pengajuan</a></li>
@endsection

@section('actions')
    <a href="{{ route('pengajuan.pdf', $p) }}" target="_blank" class="btn {{ $p->status === 'Disetujui' ? 'btn-success' : 'btn-outline-secondary' }}">
        <i class="bi bi-file-earmark-pdf me-1"></i>{{ $p->status === 'Disetujui' ? 'Unduh PDF Bertanda Tangan' : 'Pratinjau PDF' }}</a>
    @if ($p->staff_id === $me && !$p->selesai())
        <form method="post" action="{{ route('pengajuan.batal', $p) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">
            @csrf <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Batalkan</button>
        </form>
    @endif
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Isi Pengajuan
                <span class="badge text-bg-{{ \App\Models\Pengajuan::WARNA[$p->status] }}">{{ $p->status }}</span>
            </div>
            <table class="table mb-0">
                @if ($p->nomor)<tr><th style="width:35%">Nomor</th><td>{{ $p->nomor }}</td></tr>@endif
                <tr><th style="width:35%">Pengaju</th><td>{{ $p->namaPengaju($me) }}
                    @unless ($p->anonim && $p->staff_id !== $me)<br><small class="text-muted">{{ $p->staff->level ?? '' }} · {{ $p->staff->divisi ?? '' }}</small>@endunless</td></tr>
                <tr><th>Diajukan</th><td>{{ $p->created_at->translatedFormat('d F Y H:i') }}</td></tr>
                @foreach ($p->jenis->isian as $f)
                    @php($v = $p->data[$f['key']] ?? null)
                    <tr><th>{{ $f['label'] }}</th><td>
                        @if ($v === null || $v === '')
                            <span class="text-muted">-</span>
                        @elseif ($f['tipe'] === 'file')
                            <a href="{{ route('pengajuan.berkas', [$p, $f['key']]) }}" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Lihat berkas</a>
                        @elseif ($f['tipe'] === 'rupiah')
                            Rp {{ number_format($v, 0, ',', '.') }}
                        @elseif ($f['tipe'] === 'date')
                            {{ \Illuminate\Support\Carbon::parse($v)->translatedFormat('d F Y') }}
                        @else
                            {!! nl2br(e($v)) !!}
                        @endif
                    </td></tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-3">
        {{-- Riwayat persetujuan --}}
        <div class="card">
            <div class="card-header">Alur Persetujuan <small class="text-muted">({{ \App\Models\JenisPengajuan::ALUR[$p->jenis->alur] }})</small></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item"><i class="bi bi-send text-primary me-1"></i> Diajukan · {{ $p->created_at->diffForHumans() }}</li>
                @if ($p->atasan_at)
                    <li class="list-group-item"><i class="bi {{ $p->status === 'Ditolak' && !$p->hrd_at ? 'bi-x-circle text-danger' : 'bi-check-circle text-success' }} me-1"></i>
                        Atasan: {{ $p->atasan_oleh }} · {{ $p->atasan_at->diffForHumans() }}
                        @if ($p->atasan_catatan)<div class="text-muted ms-3">“{{ $p->atasan_catatan }}”</div>@endif</li>
                @endif
                @if ($p->hrd_at)
                    <li class="list-group-item"><i class="bi {{ $p->status === 'Ditolak' ? 'bi-x-circle text-danger' : 'bi-check-circle text-success' }} me-1"></i>
                        HRD: {{ $p->hrd_oleh }} · {{ $p->hrd_at->diffForHumans() }}
                        @if ($p->hrd_catatan)<div class="text-muted ms-3">“{{ $p->hrd_catatan }}”</div>@endif</li>
                @endif
                @unless ($p->selesai())
                    <li class="list-group-item text-muted"><i class="bi bi-hourglass-split me-1"></i>
                        {{ $p->status === \App\Models\Pengajuan::MENUNGGU_ATASAN ? 'Menunggu atasan: ' . ($p->staff->atasan->nama ?? '-') : 'Menunggu diproses HRD' }}</li>
                @endunless
                @if ($p->status === 'Dibatalkan')
                    <li class="list-group-item text-muted"><i class="bi bi-x-circle me-1"></i> Dibatalkan oleh pengaju</li>
                @endif
            </ul>
        </div>

        {{-- Tombol proses untuk atasan / HRD --}}
        @if ($tahap)
            <form method="post" action="{{ route('pengajuan.proses', $p) }}" class="card"><div class="card-body">
                @csrf
                <h6>Proses sebagai {{ $tahap === 'atasan' ? 'Atasan' : 'HRD' }}</h6>
                @if ($tahap === 'hrd' && $p->status === \App\Models\Pengajuan::MENUNGGU_ATASAN)
                    <div class="small text-warning mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Atasan belum memproses. Keputusan HRD langsung menjadi keputusan akhir.</div>
                @endif
                <textarea name="catatan" rows="2" class="form-control mb-2" placeholder="Catatan (opsional)"></textarea>
                <div class="d-flex gap-2">
                    <button name="keputusan" value="setujui" class="btn btn-success flex-fill"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                    <button name="keputusan" value="tolak" class="btn btn-outline-danger flex-fill" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                </div>
            </div></form>
        @endif
    </div>
</div>
@endsection
