{{-- Ringkasan SDM (dulu halaman Dashboard admin), tab pertama Dashboard Pegawai --}}
{{-- Kartu angka utama --}}
<div class="row g-3 mb-3 row-cols-2 row-cols-md-3 row-cols-xl-5">
    @foreach ($kartu as [$label, $nilai, $ikon, $url, $ket])
        <div class="col">
            <a href="{{ $url }}" class="card kartu h-100 text-decoration-none text-body">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="ikon flex-shrink-0"><i class="bi bi-{{ $ikon }}"></i></div>
                    <div class="min-w-0">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-semibold lh-1 my-1">{{ number_format($nilai) }}</div>
                        <div class="text-muted small text-truncate">{{ $ket }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    {{-- Perlu ditindaklanjuti --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-list-check me-1"></i> Perlu Ditindaklanjuti</div>
            <div class="list-group list-group-flush">
                @foreach ($tindakan as [$label, $n, $url])
                    <a href="{{ $url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi {{ $n ? 'bi-exclamation-circle text-warning' : 'bi-check-circle text-success' }} me-1"></i>{{ $label }}
                        </span>
                        <span class="badge rounded-pill {{ $n ? 'text-bg-warning' : 'text-bg-light' }}">{{ $n }}</span>
                    </a>
                @endforeach
                @php($lama = !$terakhirSinkron || $terakhirSinkron->lt(now()->subDays(7)))
                <div class="list-group-item d-flex justify-content-between align-items-center small">
                    <span><i class="bi {{ $lama ? 'bi-exclamation-circle text-warning' : 'bi-arrow-repeat text-success' }} me-1"></i>Sinkron eUniversity terakhir</span>
                    <span class="{{ $lama ? 'text-warning fw-semibold' : 'text-muted' }}">{{ $terakhirSinkron ? $terakhirSinkron->diffForHumans() : 'belum pernah' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Cuti menunggu persetujuan --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-hourglass-split me-1"></i> Cuti Menunggu</span>
                <a href="{{ $semuaCutiMenunggu }}" class="small text-decoration-none">Semua</a></div>
            <div class="list-group list-group-flush">
                @forelse ($cutiMenunggu as $c)
                    <a href="{{ route('pengajuan.show', $c) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between"><strong class="text-truncate">{{ $c->staff->nama ?? '-' }}</strong><small class="text-muted text-nowrap ms-2">{{ $c->hari }} hari</small></div>
                        <small class="text-muted">{{ $c->jenisCuti->nama }} · {{ $c->tgl_mulai->translatedFormat('d M') }} – {{ $c->tgl_selesai->translatedFormat('d M Y') }} · {{ $c->status }}</small>
                    </a>
                @empty
                    <div class="list-group-item text-muted small"><i class="bi bi-check-circle text-success me-1"></i>Tidak ada cuti yang menunggu.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sedang / akan cuti 7 hari --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar-week me-1"></i> Cuti 7 Hari ke Depan</div>
            <div class="list-group list-group-flush">
                @forelse ($akanCuti as $c)
                    @php($sedang = $c->tgl_mulai->lte(today()))
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between"><strong class="text-truncate">{{ $c->staff->nama ?? '-' }}</strong>
                            <span class="badge {{ $sedang ? 'text-bg-primary' : 'text-bg-light' }} ms-2">{{ $sedang ? 'Sedang cuti' : $c->tgl_mulai->translatedFormat('d M') }}</span></div>
                        <small class="text-muted">{{ $c->jenisCuti->nama }} · s.d. {{ $c->tgl_selesai->translatedFormat('d M Y') }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted small">Tidak ada pegawai yang cuti dalam 7 hari ke depan.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">@include('dashboard._batang', ['judul' => 'Pegawai per Divisi', 'data' => $perDivisi])</div>
    <div class="col-lg-4">@include('dashboard._batang', ['judul' => 'Jabatan Fungsional Dosen Aktif', 'data' => $jabfung])</div>
    <div class="col-lg-4">@include('dashboard._batang', ['judul' => 'Pendidikan Terakhir Dosen Aktif', 'data' => $pendidikan])</div>
</div>
