{{-- Sisa jatah cuti per jenis. Parameter: $jatah (JenisCuti::rekap), $judul?, $ajukan? (tampilkan tombol Ajukan Cuti) --}}
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check me-1"></i>{{ $judul ?? 'Sisa Jatah Cuti ' . now()->year }}</span>
        @if (!empty($ajukan) && ($idCuti = \App\Models\JenisPengajuan::where(['kode' => 'cuti', 'aktif' => true])->value('id')))
            <a href="{{ route('pengajuan.create', ['jenis' => $idCuti]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</a>
        @endif
    </div>
    <div class="card-body">
        @forelse ($jatah as $r)
            <div class="mb-2">
                <div class="d-flex justify-content-between small">
                    <span class="fw-semibold">{{ $r->jenis->nama }}</span>
                    <span>
                        @if ($r->kuota !== null)
                            sisa <strong class="{{ $r->sisa === 0 ? 'text-danger' : '' }}">{{ $r->sisa }}</strong> dari {{ $r->kuota }} hari
                        @else
                            <span class="text-muted">tanpa batas</span>
                        @endif
                    </span>
                </div>
                @if ($r->kuota)
                    {{-- Hijau = disetujui, kuning = dalam proses, abu = sisa --}}
                    <div class="progress" style="height:8px" title="Disetujui {{ $r->disetujui }} hari, dalam proses {{ $r->proses }} hari">
                        <div class="progress-bar bg-success" style="width: {{ min(100, $r->disetujui / $r->kuota * 100) }}%"></div>
                        <div class="progress-bar bg-warning" style="width: {{ min(100, $r->proses / $r->kuota * 100) }}%"></div>
                    </div>
                @endif
                @if ($r->terpakai)
                    <div class="small text-muted">terpakai {{ $r->disetujui }} hari{{ $r->proses ? " · {$r->proses} hari dalam proses" : '' }}{{ $r->jenis->keterangan ? ' · ' . $r->jenis->keterangan : '' }}</div>
                @elseif ($r->jenis->keterangan)
                    <div class="small text-muted">{{ $r->jenis->keterangan }}</div>
                @endif
            </div>
        @empty
            <div class="text-muted small">Akun belum tertaut ke data pegawai, jatah cuti belum bisa ditampilkan.</div>
        @endforelse
        @if ($jatah->isNotEmpty())
            <div class="small text-muted mt-2"><span class="badge bg-success">&nbsp;</span> disetujui <span class="badge bg-warning ms-2">&nbsp;</span> dalam proses ·
                jatah per tahun kalender, tidak diakumulasi.</div>
        @endif
    </div>
</div>
