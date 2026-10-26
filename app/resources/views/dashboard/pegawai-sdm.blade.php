@extends('layout')
@section('title', 'Dashboard')

@php
    $B = \App\Models\Kehadiran::BULAN;
    $url = fn ($ubah) => route('dashboard.pegawai', array_filter(['tab' => $tab, 'tahun' => $tahun, 'kelompok' => $kelompok] + $ubah));
    // Warna sel menit terlambat: makin lama makin merah
    $panas = fn ($v) => $v === null ? 'text-muted' : ($v >= 60 ? 'bg-danger-subtle' : ($v >= 30 ? 'bg-warning-subtle' : ''));
@endphp

@section('content')
<ul class="nav nav-tabs mb-0">
    @foreach (['ringkasan' => 'Ringkasan', 'kehadiran' => 'Kehadiran & Keterlambatan', 'turnover' => 'Turn Over', 'disc' => 'Peta Karakter (DISC)'] + (Gate::allows('admin') ? ['input' => 'Input Rekap Bulanan'] : []) as $k => $l)
        <li class="nav-item"><a class="nav-link {{ $tab === $k ? 'active' : '' }}" href="{{ route('dashboard.pegawai', ['tab' => $k, 'tahun' => $tahun]) }}">{{ $l }}</a></li>
    @endforeach
</ul>

@if ($tab === 'ringkasan')
<div class="mt-3">@include('dashboard._ringkasan')</div>
@else
<div class="card border-top-0 rounded-top-0 mb-3">
    @if (in_array($tab, ['kehadiran', 'input']))
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

@elseif ($tab === 'disc')
    @php($D = \App\Models\Disc::class)
    <form class="bar-filter rounded-0">
        <input type="hidden" name="tab" value="disc">
        <select name="tim" class="form-select" onchange="this.form.submit()">
            <option value="">Semua tim</option>
            @foreach ($daftarTim as $t)<option @selected($t === $tim)>{{ $t }}</option>@endforeach
        </select>
        @can('admin')
            <select name="staff" class="form-select" onchange="this.form.submit()">
                <option value="">Petakan / ubah pegawai…</option>
                @foreach ($pilihanStaff as $s)<option value="{{ $s->id }}">{{ $s->nama }}</option>@endforeach
            </select>
        @endcan
        <span class="info">{{ $baris->count() }} orang dipetakan</span>
    </form>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-7">
                <h6 class="small text-muted text-uppercase fw-semibold">Komitmen × Kompetensi → cara memimpin</h6>
                <table class="table table-sm table-bordered small text-center mb-0">
                    <tr class="table-light"><th class="text-start">Komitmen ↓ / Kompetensi →</th>@foreach ($D::GAYA as $k => $g)<th>{{ $k }}</th>@endforeach</tr>
                    @foreach ($matriks as $km => $baris2)
                        <tr><th class="text-start table-light">{{ $km }} <small class="text-muted fw-normal">({{ $D::GAYA[$km] }})</small></th>
                            @foreach ($baris2 as $n)<td class="{{ $n ? 'bg-primary-subtle fw-semibold' : 'text-muted' }}">{{ $n ?: '·' }}</td>@endforeach</tr>
                    @endforeach
                </table>
                <div class="small text-muted mt-1">Tinggi → Delegating, Menengah → Supporting, Cukup → Coaching, Rendah → Directing.</div>
            </div>
            <div class="col-lg-5">
                <h6 class="small text-muted text-uppercase fw-semibold">Gaya DISC dominan</h6>
                @php($maks = max(1, $dominan->max() ?? 1))
                @foreach ($dominan as $g => $n)
                    <div class="batang"><div>{{ ['D' => 'D · Dominance', 'I' => 'I · Influence', 'S' => 'S · Steadiness', 'C' => 'C · Compliance'][$g] ?? $g }}</div>
                        <div class="batang-jalur"><div class="batang-isi" style="width:{{ $n / $maks * 100 }}%"></div></div><div class="batang-nilai">{{ $n }}</div></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if ($edit)
    <form method="post" action="{{ route('dashboard.pegawai.disc') }}" class="card mb-3"><div class="card-body">
        @csrf <input type="hidden" name="staff_id" value="{{ $edit->staff_id }}">
        <h6>Peta Karakter · {{ $edit->staff->nama ?? \App\Models\Staff::whereKey($edit->staff_id)->value('nama') }}</h6>
        <div class="row g-2 small">
            <div class="col-md-3"><label class="form-label mb-0">Tim</label><input name="tim" value="{{ $edit->tim }}" list="daftarTim" class="form-control form-control-sm">
                <datalist id="daftarTim">@foreach ($daftarTim as $t)<option>{{ $t }}</option>@endforeach</datalist></div>
            <div class="col-md-2"><label class="form-label mb-0">Profil DISC</label><input name="profil" value="{{ $edit->profil }}" placeholder="mis. S / C-S" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label mb-0">Tanggal nilai</label><input type="date" name="dinilai_at" value="{{ $edit->dinilai_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="form-control form-control-sm"></div>
            @foreach ($D::KOMITMEN + $D::KOMPETENSI as $k => $l)
                <div class="col-6 col-md-1"><label class="form-label mb-0">{{ $l }}</label><input name="{{ $k }}" type="number" min="1" max="4" value="{{ $edit->$k }}" class="form-control form-control-sm"></div>
            @endforeach
        </div>
        <div class="mt-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Simpan</button>
            <a href="{{ route('dashboard.pegawai', ['tab' => 'disc', 'tim' => $tim]) }}" class="btn btn-sm btn-outline-secondary">Batal</a>
            <span class="small text-muted align-self-center">Skor 1–4. 4 pertama = komitmen, 4 berikutnya = kompetensi.</span></div>
    </div></form>
@endif

<div class="card">
    <div class="table-responsive">
    <table class="table table-sm table-hover small mb-0 align-middle">
        <thead class="table-light"><tr><th>Nama</th><th>Tim</th><th>Masa kerja</th><th>DISC</th>
            @foreach ($D::KOMITMEN + $D::KOMPETENSI as $l)<th class="text-center" title="{{ $l }}">{{ mb_substr($l, 0, 4) }}</th>@endforeach
            <th>Komitmen</th><th>Kompetensi</th>@can('admin')<th></th>@endcan</tr></thead>
        @foreach ($baris as $b)
            <tr>
                <td>{{ $b->staff->nama ?? '-' }}<br><small class="text-muted">{{ $b->staff->level ?? '' }} · {{ $b->staff->divisi ?? '' }}</small></td>
                <td class="text-muted">{{ $b->disc->tim }}</td>
                <td class="text-nowrap">{{ $b->staff?->mulai_kerja ? $b->staff->mulai_kerja->diffInYears(now()) . ' th' : '-' }}</td>
                <td><span class="badge text-bg-light">{{ $b->disc->profil ?? '-' }}</span></td>
                @foreach (array_keys($D::KOMITMEN + $D::KOMPETENSI) as $k)
                    <td class="text-center {{ ($b->disc->$k ?? 9) <= 1 ? 'bg-danger-subtle' : (($b->disc->$k ?? 0) >= 4 ? 'bg-success-subtle' : '') }}">{{ $b->disc->$k ?? '·' }}</td>
                @endforeach
                @foreach ([[$b->disc->komitmen, $b->katKomitmen], [$b->disc->kompetensi, $b->katKompetensi]] as [$nilai, $kat])
                    <td class="text-nowrap">@if ($kat){{ $nilai }} <span class="badge text-bg-{{ ['Tinggi' => 'success', 'Menengah' => 'primary', 'Cukup' => 'warning', 'Rendah' => 'danger'][$kat] }}">{{ $kat }}</span>
                        <br><small class="text-muted">{{ $D::GAYA[$kat] }}</small>@else - @endif</td>
                @endforeach
                @can('admin')<td><a href="{{ route('dashboard.pegawai', ['tab' => 'disc', 'tim' => $tim, 'staff' => $b->disc->staff_id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>@endcan
            </tr>
        @endforeach
    </table>
    </div>
</div>

@else
    <form method="post" action="{{ route('dashboard.pegawai.simpan') }}">
        @csrf <input type="hidden" name="tahun" value="{{ $tahun }}"><input type="hidden" name="bulan" value="{{ $bulan }}">
        <div class="table-responsive" style="max-height:560px">
        <table class="table table-sm small mb-0 align-middle">
            <thead class="table-light sticky-top"><tr><th>Nama</th><th>Divisi</th><th style="width:150px">Terlambat (menit)</th><th style="width:150px">Tidak hadir (hari)</th></tr></thead>
            @foreach ($baris as $o)
                <tr><td>{{ $o->nama }}</td><td class="text-muted">{{ $o->divisi }}</td>
                    <td><input name="terlambat[{{ $o->id }}]" type="number" min="0" value="{{ $isian[$o->id]->terlambat_menit ?? '' }}" class="form-control form-control-sm"></td>
                    <td><input name="absen[{{ $o->id }}]" type="number" min="0" max="31" step="0.5" value="{{ isset($isian[$o->id]->tidak_hadir_hari) ? $isian[$o->id]->tidak_hadir_hari + 0 : '' }}" class="form-control form-control-sm"></td></tr>
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
@endif
@endsection
