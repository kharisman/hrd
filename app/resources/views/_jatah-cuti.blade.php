{{-- Sisa jatah cuti per jenis sebagai donat (CSS conic-gradient, tanpa library).
     Parameter: $jatah (JenisCuti::rekap), $judul?, $ajukan? (tampilkan tombol Ajukan Cuti) --}}
@php($berkuota = $jatah->filter(fn ($r) => $r->kuota))
@php($bebas = $jatah->reject(fn ($r) => $r->kuota))
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check me-1"></i>{{ $judul ?? 'Sisa Jatah Cuti ' . now()->year }}</span>
        @if (!empty($ajukan) && ($idCuti = \App\Models\JenisPengajuan::where(['kode' => 'cuti', 'aktif' => true])->value('id')))
            <a href="{{ route('pengajuan.create', ['jenis' => $idCuti]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</a>
        @endif
    </div>
    <div class="card-body">
        @if ($jatah->isEmpty())
            <div class="text-muted small">Akun belum tertaut ke data pegawai, jatah cuti belum bisa ditampilkan.</div>
        @else
            <div class="d-flex flex-wrap gap-4">
                @foreach ($berkuota as $r)
                    @php($a = min(360, ($r->disetujui + ($r->bersama ?? 0)) / $r->kuota * 360))
                    @php($b = min(360, $a + $r->proses / $r->kuota * 360))
                    <div class="text-center" style="width:120px">
                        <div class="mx-auto rounded-circle position-relative" role="img"
                             aria-label="{{ $r->jenis->nama }}: disetujui {{ $r->disetujui }}, proses {{ $r->proses }}, sisa {{ $r->sisa }} dari {{ $r->kuota }} hari"
                             title="Disetujui {{ $r->disetujui }} · proses {{ $r->proses }} · sisa {{ $r->sisa }} hari"
                             style="width:110px;height:110px;background:conic-gradient(var(--bs-success) 0deg {{ $a }}deg, var(--bs-warning) {{ $a }}deg {{ $b }}deg, var(--bs-secondary-bg) {{ $b }}deg 360deg)">
                            <div class="position-absolute top-50 start-50 translate-middle rounded-circle bg-body d-grid" style="width:76px;height:76px;place-content:center;line-height:1.1">
                                <strong class="fs-4 {{ $r->sisa === 0 ? 'text-danger' : '' }}">{{ $r->sisa }}</strong>
                                <small class="text-muted">dari {{ $r->kuota }}</small>
                            </div>
                        </div>
                        <div class="small fw-semibold mt-2">{{ $r->jenis->nama }}</div>
                        <div class="small text-muted">sisa hari{{ $r->proses ? " · {$r->proses} proses" : '' }}{{ !empty($r->bersama) ? " · {$r->bersama} cuti bersama" : '' }}</div>
                    </div>
                @endforeach
            </div>
            @if ($bebas->isNotEmpty())
                <div class="small mt-3 pt-2 border-top"><span class="text-muted">Tanpa batas:</span>
                    @foreach ($bebas as $r)<span class="badge text-bg-light border ms-1">{{ $r->jenis->nama }}@if ($r->terpakai) · {{ $r->terpakai }} hari @endif</span>@endforeach
                </div>
            @endif
            <div class="small text-muted mt-2"><span class="badge bg-success">&nbsp;</span> disetujui <span class="badge bg-warning ms-2">&nbsp;</span> dalam proses
                <span class="badge bg-secondary-subtle border ms-2">&nbsp;</span> sisa · jatah per tahun kalender, tidak diakumulasi.</div>
        @endif
    </div>
</div>
