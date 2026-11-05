@extends('layout')
@section('title', $p->jenis->nama)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengajuan.index') }}" class="text-decoration-none">Pengajuan</a></li>
@endsection

@section('actions')
    <a href="{{ route('pengajuan.pdf', $p) }}" target="_blank" class="btn {{ $p->status === 'Disetujui' ? 'btn-success' : 'btn-outline-secondary' }}">
        <i class="bi bi-file-earmark-pdf me-1"></i>{{ $p->status === 'Disetujui' ? 'Unduh PDF Bertanda Tangan' : 'Pratinjau PDF' }}</a>
    @if ($bolehBatal)
        <form method="post" action="{{ route('pengajuan.batal', $p) }}" onsubmit="return confirm('Batalkan pengajuan ini? Hanya bisa selama belum diproses.')">
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
                <span class="badge text-bg-{{ \App\Models\Pengajuan::warna($p->status) }}">{{ $p->status }}</span>
            </div>
            <table class="table mb-0">
                @if ($p->nomor)<tr><th style="width:35%">Nomor</th><td>{{ $p->nomor }}</td></tr>@endif
                <tr><th style="width:35%">Pengaju</th><td>{{ $p->namaPengaju($me) }}
                    @unless ($p->anonim && $p->staff_id !== $me)<br><small class="text-muted">{{ $p->staff->level ?? '' }} · {{ $p->staff->divisi ?? '' }}</small>@endunless</td></tr>
                <tr><th>Diajukan</th><td>{{ $p->created_at->translatedFormat('d F Y H:i') }}</td></tr>
                @if ($p->jenis_cuti_id)
                    <tr><th>Jenis Cuti</th><td>{{ $p->jenisCuti->nama }}</td></tr>
                    <tr><th>Tanggal</th><td>{{ $p->tgl_mulai->translatedFormat('d M Y') }} – {{ $p->tgl_selesai->translatedFormat('d M Y') }} <span class="text-muted">({{ $p->hari }} hari)</span></td></tr>
                @endif
                @foreach ($p->jenis->isian as $f)
                    @php($v = $p->data[$f['key']] ?? null)
                    <tr><th>{{ $f['label'] }}</th><td>
                        @if ($v === null || $v === '')
                            <span class="text-muted">-</span>
                        @elseif ($f['tipe'] === 'file')
                            <a href="{{ route('pengajuan.berkas', [$p, $f['key']]) }}" target="_blank"><i class="bi bi-file-earmark-text me-1"></i>Lihat berkas</a>
                        @elseif ($f['tipe'] === 'pegawai')
                            {{ $v }} @if ($hp = \App\Models\JenisPengajuan::hpPegawai($v))<br><a href="https://wa.me/{{ preg_replace(['/\D/', '/^0/'], ['', '62'], $hp) }}" target="_blank" class="small"><i class="bi bi-whatsapp me-1"></i>{{ $hp }}</a>@endif
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
        {{-- Alur: tahap selesai (dengan keputusan), tahap berjalan, tahap berikutnya --}}
        <div class="card">
            <div class="card-header">Alur Persetujuan <small class="text-muted">({{ \App\Models\JenisPengajuan::teksAlur($p->tahapan ?? []) }})</small></div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item"><i class="bi bi-send text-primary me-1"></i> Diajukan · {{ $p->created_at->diffForHumans() }}</li>
                @foreach ($p->tahapan ?? [] as $i => $t)
                    @php($k = $p->riwayat[$i] ?? null)
                    @php($label = \App\Models\JenisPengajuan::TAHAP[$t] ?? $t)
                    @if ($k)
                        <li class="list-group-item"><i class="bi {{ $k['setuju'] ? 'bi-check-circle text-success' : 'bi-x-circle text-danger' }} me-1"></i>
                            {{ $label }}: {{ $k['oleh'] }} · {{ \Illuminate\Support\Carbon::parse($k['at'])->diffForHumans() }}
                            @if ($k['catatan'])<div class="text-muted ms-3">“{{ $k['catatan'] }}”</div>@endif</li>
                    @elseif ($i === $p->tahap_ke && !$p->selesai())
                        <li class="list-group-item fw-semibold"><i class="bi bi-hourglass-split text-warning me-1"></i>
                            Menunggu {{ $label }}{{ $t === 'atasan' ? ': ' . ($p->staff->atasan->nama ?? '-') : '' }}</li>
                    @elseif (!$p->selesai())
                        <li class="list-group-item text-muted"><i class="bi bi-circle me-1"></i> {{ $label }}</li>
                    @endif
                @endforeach
                @if ($p->status === 'Dibatalkan')
                    <li class="list-group-item text-muted"><i class="bi bi-x-circle me-1"></i> Dibatalkan oleh pengaju</li>
                @endif
            </ul>
        </div>

        @if ($p->notifikasi)
            <div class="card">
                <div class="card-header"><i class="bi bi-telegram me-1"></i>Notifikasi Telegram</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($p->notifikasi as $n)
                        <li class="list-group-item">
                            @if (!empty($n['error']))<i class="bi bi-exclamation-triangle text-danger me-1"></i>
                            @elseif (!empty($n['dilewati']))<i class="bi bi-exclamation-circle text-warning me-1"></i>
                            @else<i class="bi bi-check2-all text-success me-1"></i>@endif
                            Ke {{ $n['untuk'] }} · {{ \Illuminate\Support\Carbon::parse($n['at'])->diffForHumans() }}
                            @if ($n['terkirim'])<div class="text-muted ms-3">Terkirim: {{ implode(', ', $n['terkirim']) }}</div>@endif
                            @foreach ($n['dilewati'] as $d)<div class="text-warning-emphasis ms-3">{{ $d['penerima'] ?? '?' }}: {{ str_replace('_', ' ', $d['alasan'] ?? 'gagal') }}</div>@endforeach
                            @if (!empty($n['error']))<div class="text-danger ms-3">{{ $n['error'] }}{{ $n['kepada'] ? '' : ' (tidak ada penerima)' }}</div>@endif
                            @if (!empty($n['id']))<div class="text-muted ms-3"><code class="small">{{ $n['id'] }}</code></div>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Pengajuan sendiri tidak bisa diproses sendiri walau pemegang tahapnya (mis. admin HRD) --}}
        @if (!$tahap && !$p->selesai() && $bolehLihatSendiri)
            <div class="alert alert-light border small mb-0"><i class="bi bi-info-circle me-1"></i>
                Ini pengajuan Anda sendiri, jadi tidak bisa Anda proses. Tahap <strong>{{ \App\Models\JenisPengajuan::TAHAP[$p->tahapSekarang()] ?? '' }}</strong>
                diproses oleh: {{ $pemutusLain ?: 'belum ada pemutus lain' }}.</div>
        @endif

        {{-- Tombol proses untuk pemutus tahap yang sedang berjalan --}}
        @if ($tahap)
            <form method="post" action="{{ route('pengajuan.proses', $p) }}" class="card"><div class="card-body">
                @csrf
                <h6>Proses sebagai {{ \App\Models\JenisPengajuan::TAHAP[$tahap] }}</h6>
                @if ($p->jenis_cuti_id)
                    {{-- SOP: jadwal ulang bila kebutuhan institusi; HRD boleh mengoreksi lama cuti --}}
                    <details class="mb-2 small"><summary class="text-primary">Jadwal ulang / koreksi lama cuti</summary>
                        <div class="row g-2 mt-1">
                            <div class="col-6"><label class="form-label small mb-0">Mulai</label><input type="date" name="tgl_mulai" value="{{ $p->tgl_mulai->toDateString() }}" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="form-label small mb-0">Selesai</label><input type="date" name="tgl_selesai" value="{{ $p->tgl_selesai->toDateString() }}" class="form-control form-control-sm"></div>
                            @if ($tahap === 'hrd')
                                <div class="col-6"><label class="form-label small mb-0">Lama (hari)</label><input type="number" min="1" name="lama" value="{{ $p->hari }}" class="form-control form-control-sm"></div>
                            @endif
                        </div>
                        <div class="form-text">Berlaku saat <strong>Setujui</strong> dan tercatat di riwayat. Lama dihitung ulang otomatis bila tanggal diubah.</div>
                    </details>
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
