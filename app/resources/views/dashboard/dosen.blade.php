@extends('layout')
@section('title', 'Dashboard Dosen')

@php
    $J = \App\Http\Controllers\DashboardDosenController::class;
    // Grafik batang horizontal: [label => nilai]
    $batang = function (array $data, string $warna = '') {
        $maks = max(1, max($data ?: [0]));
        $html = '';
        foreach ($data as $label => $n) {
            $html .= '<div class="batang"><div class="text-truncate">' . e($label) . '</div><div class="batang-jalur"><div class="batang-isi ' . ($n ? $warna : 'kosong') . '" style="width:' . ($n / $maks * 100) . '%"></div></div><div class="batang-nilai">' . $n . '</div></div>';
        }
        return $html;
    };
    $taTerakhir = $rasio->keys()->last();
@endphp

@section('content')
@include('dashboard._tab-dosen')
{{-- Filter prodi --}}
<ul class="nav nav-pills mb-3 small">
    <li class="nav-item"><a class="nav-link {{ !$prodi ? 'active' : '' }}" href="{{ route('dashboard.dosen') }}">Semua Prodi</a></li>
    @foreach (\App\Models\Dosen::daftarProdi() as $p)
        <li class="nav-item"><a class="nav-link {{ $prodi === $p ? 'active' : '' }}" href="{{ route('dashboard.dosen', ['prodi' => $p]) }}">{{ $p }}</a></li>
    @endforeach
</ul>

{{-- Angka utama --}}
<div class="row g-3 mb-3 row-cols-1 row-cols-sm-2 row-cols-xl-3 row-cols-xxl-5">
    @foreach ([
        ['Dosen aktif', $ringkas['jumlah'], 'people', 'tetap di prodi'],
        ['Serdos', $ringkas['pctSerdos'] . '%', 'patch-check', $ringkas['serdos'] . ' tersertifikasi'],
        ['Pendidikan S3', $ringkas['pctS3'] . '%', 'mortarboard', 'target unggul ' . $J::TARGET_S3 . '%'],
        ['Lektor Kepala+', $ringkas['pctLK'] . '%', 'award', 'target unggul ' . $J::TARGET_LK . '%'],
        ['Jafung tepat waktu', $ringkas['tepat'] . '/' . $ringkas['jumlah'], 'clock-history', 'sesuai masa kerja'],
    ] as [$judul, $nilai, $ikon, $ket])
        <div class="col"><div class="card kartu h-100"><div class="card-body d-flex gap-3 align-items-center">
            <div class="ikon flex-shrink-0"><i class="bi bi-{{ $ikon }}"></i></div>
            <div><div class="small text-muted">{{ $judul }}</div><div class="fs-4 fw-semibold lh-1">{{ $nilai }}</div><div class="small text-muted">{{ $ket }}</div></div>
        </div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4"><div class="card h-100">
        <div class="card-header">Jabatan Fungsional</div>
        <div class="card-body">{!! $batang(array_combine($J::SINGKAT, $ringkas['jafung'])) !!}
            <div class="small text-muted mt-2">Tepat waktu {{ $ringkas['tepat'] }} · tidak tepat {{ $ringkas['tidakTepat'] }} (acuan: Th1 Non JFA, Th2–3 AA, Th4–8 Lektor, Th9+ LK)</div></div>
    </div></div>
    <div class="col-lg-4"><div class="card h-100">
        <div class="card-header">Sertifikasi Dosen</div>
        <div class="card-body">{!! $batang(['Sudah' => $ringkas['serdos'], 'Eligible' => $ringkas['eligible'], 'Belum eligible' => $ringkas['nonEligible']]) !!}
            <div class="small text-muted mt-2">Eligible: minimal Asisten Ahli & 2 tahun sebagai dosen.</div></div>
    </div></div>
    <div class="col-lg-4"><div class="card h-100">
        <div class="card-header">Pendidikan Terakhir</div>
        <div class="card-body">{!! $batang(['S2' => $ringkas['s2'], 'S3' => $ringkas['s3']]) !!}
            <div class="small text-muted mt-2">Akreditasi unggul: S3 ≥ {{ $J::TARGET_S3 }}% (kurang {{ max(0, (int) ceil($ringkas['jumlah'] * $J::TARGET_S3 / 100) - $ringkas['s3']) }} orang).</div></div>
    </div></div>
</div>

{{-- Rincian per dosen --}}
<div class="card mb-3">
    <div class="card-header">Jafung & Serdos per Dosen <small class="text-muted">· {{ $dosen->count() }} dosen</small></div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle small">
        <thead class="table-light"><tr><th>Nama</th>@unless ($prodi)<th>Prodi</th>@endunless<th>TMT Dosen</th><th>Jafung</th><th>TMT Jafung</th><th>Seharusnya</th><th>Naik ke</th><th>Serdos</th></tr></thead>
        <tbody>
        @forelse ($dosen as $d)
            <tr>
                <td><a href="{{ route('dosen.show', $d->model) }}" class="text-decoration-none">{{ $d->model->nama }}</a></td>
                @unless ($prodi)<td>{{ $d->prodi }}</td>@endunless
                <td class="text-nowrap">{{ ($d->model->tmt_dosen ?? $d->model->mulai_kerja)?->format('d/m/Y') ?? '-' }}<br><small class="text-muted">{{ $d->masa !== null ? $d->masa . ' th' : '' }}</small></td>
                <td>{{ $d->jafung }}</td>
                <td class="text-nowrap">{{ $d->model->tmt_jabfung?->format('d/m/Y') ?? '-' }}</td>
                <td>@if ($d->seharusnya !== null)
                        {{ $J::SINGKAT[$d->seharusnya] }} <span class="badge text-bg-{{ $d->tepat ? 'success' : 'warning' }}">{{ $d->tepat ? 'Tepat' : 'Tertinggal' }}</span>
                    @else - @endif</td>
                <td class="text-nowrap">@if ($d->jenjang < 4)
                        {{ $d->berikut }} <small class="text-muted">{{ $d->layakNaik ? ($d->bisaNaik ? '· sudah bisa' : '· ' . $d->layakNaik->translatedFormat('M Y')) : '' }}</small>
                    @endif</td>
                <td>@if ($d->serdos)<span class="badge text-bg-success">Serdos</span>
                    @elseif ($d->eligibleSerdos)<span class="badge text-bg-info">Eligible</span>
                    @else<span class="badge text-bg-light">Belum</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada dosen aktif dengan prodi ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="row g-3">
    {{-- Summary kualifikasi per prodi --}}
    <div class="col-xl-7"><div class="card h-100">
        <div class="card-header">Summary Kualifikasi Dosen per Prodi</div>
        <div class="table-responsive">
        <table class="table table-sm mb-0 small">
            <thead class="table-light"><tr><th>Aspek</th>@foreach ($perProdi as $p => $x)<th>{{ $p }}</th>@endforeach</tr></thead>
            @foreach ([
                'Jumlah dosen' => fn ($x) => $x['jumlah'] . ' orang',
                'Serdos' => fn ($x) => "{$x['serdos']}/{$x['jumlah']} ({$x['pctSerdos']}%)",
                'Pendidikan S3' => fn ($x) => "{$x['s3']}/{$x['jumlah']} ({$x['pctS3']}%)",
                'Lektor Kepala ke atas' => fn ($x) => ($x['jafung'][3] + $x['jafung'][4]) . "/{$x['jumlah']} ({$x['pctLK']}%)",
                'Jafung tepat waktu' => fn ($x) => "{$x['tepat']}/{$x['jumlah']}",
                'Eligible serdos (belum)' => fn ($x) => $x['eligible'] . ' orang',
            ] as $aspek => $f)
                <tr><td>{{ $aspek }}</td>@foreach ($perProdi as $x)<td>{{ $f($x) }}</td>@endforeach</tr>
            @endforeach
        </table>
        </div>
    </div></div>

    {{-- Rasio dosen : mahasiswa --}}
    <div class="col-xl-5"><div class="card h-100">
        <div class="card-header">Rasio Dosen : Mahasiswa</div>
        <div class="table-responsive">
        <table class="table table-sm mb-0 small">
            <thead class="table-light"><tr><th>TA</th><th>Prodi</th><th class="text-end">Dosen</th><th class="text-end">Mhs</th><th class="text-end">Rasio</th><th class="text-end">Kurang</th></tr></thead>
            @forelse ($rasio->reverse() as $ta => $baris)
                @foreach ($baris as $x)
                    {{-- TA berjalan memakai jumlah dosen aktif sekarang --}}
                    @php($nDosen = $jumlahPerProdi[$x->program_studi] ?? 0)
                    @php($kurang = (int) ceil($x->jumlah_mahasiswa / $x->target) - $nDosen)
                    <tr class="{{ $ta === $taTerakhir ? '' : 'text-muted' }}">
                        <td>{{ $loop->first ? $ta : '' }}</td><td>{{ $x->program_studi }}</td>
                        <td class="text-end">{{ $ta === $taTerakhir ? $nDosen : '·' }}</td>
                        <td class="text-end" @if ($x->per_angkatan) title="{{ collect($x->per_angkatan)->map(fn ($n, $kode) => 'Angkatan ' . ($labelAngkatan[$x->program_studi][$kode] ?? $kode) . ': ' . $n)->join(PHP_EOL) }}" @endif>{{ $x->jumlah_mahasiswa }}@if ($x->sumber === 'api') <i class="bi bi-cloud-check text-success" title="Dari eUniversity (mahasiswa aktif)"></i>@endif</td>
                        <td class="text-end">@if ($ta === $taTerakhir && $nDosen) 1 : {{ round($x->jumlah_mahasiswa / $nDosen, 1) }} @endif</td>
                        <td class="text-end {{ $ta === $taTerakhir && $kurang > 0 ? 'text-danger fw-semibold' : '' }}">{{ $ta === $taTerakhir ? max(0, $kurang) : '' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" class="text-muted">Belum ada data jumlah mahasiswa.</td></tr>
            @endforelse
        </table>
        </div>
        @if ($bolehUbah)
            <form method="post" action="{{ route('dashboard.dosen.rasio') }}" class="card-footer bg-white small">
                @csrf
                <div class="fw-semibold mb-1">Isi jumlah mahasiswa</div>
                <div class="d-flex flex-wrap gap-2 align-items-end">
                    <div><label class="form-label mb-0">TA</label>
                        @if ($periodeTa->isNotEmpty())
                            <select name="tahun_ajaran" class="form-select form-select-sm" style="width:120px">@foreach ($periodeTa as $t)<option @selected($t === $taTerakhir)>{{ $t }}</option>@endforeach</select>
                        @else
                            <input name="tahun_ajaran" value="{{ $taTerakhir }}" class="form-control form-control-sm" style="width:100px" required>
                        @endif</div>
                    @foreach (\App\Models\Dosen::daftarProdi() as $p)
                        <div><label class="form-label mb-0">{{ $p }}</label><input name="mahasiswa[{{ $p }}]" type="number" min="0" class="form-control form-control-sm" style="width:110px"
                            value="{{ $rasio[$taTerakhir]?->firstWhere('program_studi', $p)?->jumlah_mahasiswa }}"></div>
                    @endforeach
                    <div><label class="form-label mb-0">Target</label><input name="target" type="number" value="22" min="1" class="form-control form-control-sm" style="width:70px"></div>
                    <button class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        @endif
    </div></div>
</div>
@endsection
