@extends('layout')
@section('title', 'Dashboard Pegawai')

@php
    $B = \App\Models\Kehadiran::BULAN;
    $url = fn ($ubah) => route('dashboard.pegawai', array_filter(['tab' => $tab, 'tahun' => $tahun, 'kelompok' => $kelompok] + $ubah));
    // Warna sel menit terlambat: makin lama makin merah
    $panas = fn ($v) => $v === null ? 'text-muted' : ($v >= 60 ? 'bg-danger-subtle' : ($v >= 30 ? 'bg-warning-subtle' : ''));
@endphp

@section('content')
<ul class="nav nav-tabs mb-0">
    @foreach (['kehadiran' => 'Kehadiran & Keterlambatan', 'turnover' => 'Turn Over'] + (Gate::allows('admin') ? ['input' => 'Input Rekap Bulanan'] : []) as $k => $l)
        <li class="nav-item"><a class="nav-link {{ $tab === $k ? 'active' : '' }}" href="{{ route('dashboard.pegawai', ['tab' => $k, 'tahun' => $tahun]) }}">{{ $l }}</a></li>
    @endforeach
</ul>

<div class="card border-top-0 rounded-top-0 mb-3">
    @if ($tab !== 'turnover')
    <form class="bar-filter rounded-0">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <select name="tahun" class="form-select" onchange="this.form.submit()">@foreach ($daftarTahun as $t)<option @selected($t == $tahun)>{{ $t }}</option>@endforeach</select>
        @if ($tab === 'kehadiran')
            <select name="kelompok" class="form-select" onchange="this.form.submit()">
                <option value="">Semua kelompok</option>
                @foreach ($daftarKelompok as $k)<option @selected($k === $kelompok)>{{ $k }}</option>@endforeach
            </select>
            <span class="info">{{ $baris->count() }} orang{{ $tanpaData ? " · {$tanpaData} belum ada data" : '' }}</span>
        @else
            <select name="bulan" class="form-select" onchange="this.form.submit()">@foreach ($B as $i => $b)<option value="{{ $i }}" @selected($i == $bulan)>{{ $b }}</option>@endforeach</select>
        @endif
    </form>
    @endif

@if ($tab === 'kehadiran')
    <div class="card-body">
        <div class="row g-3">
            @foreach (['Rata-rata keterlambatan (menit / orang)' => $rataTerlambat, 'Rata-rata tidak hadir (hari / orang)' => $rataAbsen] as $judul => $rata)
                <div class="col-xl-6">
                    <h6 class="small text-muted text-uppercase fw-semibold">{{ $judul }}</h6>
                    <div class="table-responsive">
                    <table class="table table-sm small mb-0">
                        <thead class="table-light"><tr><th>Kelompok</th>@foreach ($B as $b)<th class="text-end">{{ $b }}</th>@endforeach</tr></thead>
                        @foreach ($daftarKelompok as $k)
                            <tr class="{{ $k === $kelompok ? 'table-primary' : '' }}"><td class="text-nowrap">{{ $k }}</td>
                                @foreach ($rata[$k] ?? [] as $v)<td class="text-end {{ $judul[0] === 'R' && str_contains($judul, 'menit') ? $panas($v) : '' }}">{{ $v ?? '·' }}</td>@endforeach</tr>
                        @endforeach
                    </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4"><div class="card h-100">
        <div class="card-header">10 Terbanyak Terlambat {{ $tahun }}</div>
        <div class="card-body">
            @php($maks = max(1, $terlambatTeratas->max('totalTerlambat') ?? 1))
            @forelse ($terlambatTeratas as $o)
                <div class="batang"><div class="text-truncate" title="{{ $o->nama }}">{{ $o->nama }}</div>
                    <div class="batang-jalur"><div class="batang-isi" style="width:{{ $o->totalTerlambat / $maks * 100 }}%"></div></div>
                    <div class="batang-nilai">{{ $o->totalTerlambat }}</div></div>
            @empty
                <div class="text-muted small">Belum ada data keterlambatan.</div>
            @endforelse
            <div class="small text-muted mt-2">Total menit dalam setahun.</div>
        </div>
    </div></div>
    <div class="col-xl-8"><div class="card h-100">
        <div class="card-header">Rincian per Pegawai <small class="text-muted">· menit terlambat / hari tidak hadir</small></div>
        <div class="table-responsive" style="max-height:520px">
        <table class="table table-sm small mb-0 align-middle">
            <thead class="table-light sticky-top"><tr><th>Nama</th>@foreach ($B as $b)<th class="text-end">{{ $b }}</th>@endforeach<th class="text-end">Total</th></tr></thead>
            @foreach ($baris as $o)
                <tr>
                    <td class="text-nowrap">{{ \Illuminate\Support\Str::limit($o->nama, 28) }}<br><small class="text-muted">{{ $o->kelompok }}</small></td>
                    @foreach ($B as $i => $b)
                        <td class="text-end {{ $panas($o->terlambat[$i]) }}">{{ $o->terlambat[$i] ?? '·' }}@if ($o->absen[$i])<br><span class="badge text-bg-secondary">{{ $o->absen[$i] + 0 }} hr</span>@endif</td>
                    @endforeach
                    <td class="text-end fw-semibold">{{ $o->totalTerlambat }}@if ($o->totalAbsen)<br><span class="badge text-bg-secondary">{{ $o->totalAbsen + 0 }} hr</span>@endif</td>
                </tr>
            @endforeach
        </table>
        </div>
        <div class="card-footer small text-muted">Kuning ≥ 30 menit, merah ≥ 60 menit dalam sebulan. Label abu-abu = hari tidak hadir.</div>
    </div></div>
</div>

@elseif ($tab === 'turnover')
    <div class="card-body">
        <p class="small text-muted mb-2">Turn over = jumlah yang keluar di tahun itu ÷ jumlah orang per 1 Januari. Dihitung dari tanggal keluar pegawai.
            @if ($tanpaTanggal)<span class="text-warning">{{ $tanpaTanggal }} pegawai nonaktif belum punya tanggal keluar, jadi belum terhitung.</span>@endif</p>
        <div class="table-responsive">
        <table class="table table-sm small mb-0">
            <thead class="table-light"><tr><th>Kelompok</th>@foreach ($tahunList as $t)<th class="text-end">{{ $t }}</th>@endforeach</tr></thead>
            @foreach ($turnover as $k => $per)
                <tr><td class="text-nowrap">{{ $k }}</td>
                    @foreach ($per as $x)
                        <td class="text-end {{ ($x['rasio'] ?? 0) >= 0.2 ? 'bg-danger-subtle' : (($x['rasio'] ?? 0) >= 0.1 ? 'bg-warning-subtle' : '') }}"
                            title="{{ $x['nama']->join(', ') }}">
                            @if ($x['rasio'] !== null){{ round($x['rasio'] * 100) }}% <small class="text-muted">({{ $x['keluar'] }}/{{ $x['awal'] }})</small>@else · @endif</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">Yang Keluar per Tahun</div>
    <ul class="list-group list-group-flush small">
        @foreach (array_reverse($tahunList) as $t)
            @php($nama = $turnover->map(fn ($p, $k) => $p[$t]['nama']->map(fn ($n) => "$n ($k)"))->flatten())
            @if ($nama->isNotEmpty())<li class="list-group-item"><strong>{{ $t }}</strong> · {{ $nama->join(', ') }}</li>@endif
        @endforeach
    </ul>
</div>

@else
    <form method="post" action="{{ route('dashboard.pegawai.simpan') }}">
        @csrf <input type="hidden" name="tahun" value="{{ $tahun }}"><input type="hidden" name="bulan" value="{{ $bulan }}">
        <div class="table-responsive" style="max-height:560px">
        <table class="table table-sm small mb-0 align-middle">
            <thead class="table-light sticky-top"><tr><th>Nama</th><th>Divisi</th><th style="width:150px">Terlambat (menit)</th><th style="width:150px">Tidak hadir (hari)</th></tr></thead>
            @foreach ($baris as $o)
                <tr><td>{{ $o->nama }}</td><td class="text-muted">{{ $o->divisi }}</td>
                    <td><input name="terlambat[{{ $o->nip }}]" type="number" min="0" value="{{ $isian[$o->nip]->terlambat_menit ?? '' }}" class="form-control form-control-sm"></td>
                    <td><input name="absen[{{ $o->nip }}]" type="number" min="0" max="31" step="0.5" value="{{ isset($isian[$o->nip]->tidak_hadir_hari) ? $isian[$o->nip]->tidak_hadir_hari + 0 : '' }}" class="form-control form-control-sm"></td></tr>
            @endforeach
        </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="small text-muted">Rekap {{ $B[$bulan] }} {{ $tahun }} · isian kosong tidak mengubah data.</span>
            <button class="btn btn-primary">Simpan Rekap</button>
        </div>
    </form>
</div>
@endif
@endsection
