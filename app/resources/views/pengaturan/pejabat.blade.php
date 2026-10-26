@extends('layout')
@section('title', 'Peran Pejabat')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Pemegang peran menyetujui pengajuan pada tahap yang sesuai di <a href="{{ route('jenis-pengajuan.index') }}">alur jenis pengajuan</a>.
    Satu peran boleh dipegang beberapa pegawai; cukup salah satu yang memutus. Tahap <strong>Atasan</strong> mengikuti
    <a href="{{ route('staff.atasan') }}">Atasan & Bawahan</a>, tahap <strong>HRD</strong> dipegang admin HRD.
</div>
<div class="row g-3 row-cols-1 row-cols-md-2">
    @foreach (\App\Models\Pejabat::PERAN as $kode => $nama)
        <div class="col">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between"><strong>{{ $nama }}</strong>
                    <span class="badge text-bg-light">{{ count($pemegang[$kode] ?? []) }} orang</span></div>
                <ul class="list-group list-group-flush">
                    @forelse ($pemegang[$kode] ?? [] as $p)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $p->staff->nama ?? '#' . $p->staff_id }}
                                <br><small class="text-muted">{{ $p->staff->level ?? '' }} · {{ $p->staff->divisi ?? '' }}</small></span>
                            <form method="post" action="{{ route('pengaturan.pejabat.hapus', $p) }}" onsubmit="return confirm('Lepas {{ $p->staff->nama ?? '' }} dari {{ $nama }}?')">
                                @csrf @method('delete') <button class="btn btn-sm btn-outline-danger" title="Lepas"><i class="bi bi-x"></i></button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">Belum ada pemegang. Pengajuan yang alurnya melewati {{ $nama }} akan tertahan.</li>
                    @endforelse
                </ul>
                <div class="card-footer bg-white">
                    <form method="post" action="{{ route('pengaturan.pejabat.tambah') }}" class="d-flex gap-2">
                        @csrf <input type="hidden" name="peran" value="{{ $kode }}">
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Tambah pegawai…</option>
                            @foreach ($pegawai as $s)<option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->level }}, {{ $s->divisi }})</option>@endforeach
                        </select>
                        <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Tetapkan</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
