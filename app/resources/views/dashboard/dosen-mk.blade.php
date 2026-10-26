@extends('layout')
@section('title', 'Penguasaan Mata Kuliah')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.dosen') }}" class="text-decoration-none">Dashboard Dosen</a></li>
@endsection

@section('actions')
    @if ($bolehUbah && $kurikulum)
        @if ($ubah)
            <a href="{{ route('dashboard.dosen.mk', ['prodi' => $prodi, 'kurikulum' => $kurikulum]) }}" class="btn btn-outline-secondary">Selesai</a>
        @else
            <a href="{{ route('dashboard.dosen.mk', ['prodi' => $prodi, 'kurikulum' => $kurikulum, 'ubah' => 1]) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Ubah Matriks</a>
        @endif
    @endif
@endsection

@php
    $tercapai = $mk->filter(fn ($m) => $m->gap >= 0)->count();
    $perDosen = $dosen->mapWithKeys(fn ($d) => [$d->id => $mk->filter(fn ($m) => in_array($d->id, $m->kuasai))->count()]);
@endphp

@section('content')
@include('dashboard._tab-dosen')

<div class="d-flex flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills small">
        @foreach (\App\Models\Dosen::daftarProdi() as $p)
            <li class="nav-item"><a class="nav-link {{ $prodi === $p ? 'active' : '' }}" href="{{ route('dashboard.dosen.mk', ['prodi' => $p]) }}">{{ $p }}</a></li>
        @endforeach
    </ul>
    @if ($daftarKurikulum->count() > 1)
        <div class="btn-group btn-group-sm ms-auto">
            @foreach ($daftarKurikulum as $k)
                <a href="{{ route('dashboard.dosen.mk', ['prodi' => $prodi, 'kurikulum' => $k, 'ubah' => $ubah ?: null]) }}" class="btn btn-outline-secondary {{ $k === $kurikulum ? 'active' : '' }}">Kurikulum {{ $k }}</a>
            @endforeach
        </div>
    @endif
</div>

@if (!$kurikulum)
    <div class="alert alert-light border">Belum ada mata kuliah inti untuk {{ $prodi }}. @if ($bolehUbah) Tambahkan di bawah. @endif</div>
@else
<div class="row g-3 mb-3 row-cols-2 row-cols-lg-4">
    @foreach ([
        ['Mata kuliah inti', $mk->count(), 'journal-text', 'kurikulum ' . $kurikulum],
        ['Tercapai', $tercapai, 'check-circle', $mk->count() ? round($tercapai / $mk->count() * 100) . '% MK' : ''],
        ['Tidak tercapai', $mk->count() - $tercapai, 'exclamation-triangle', 'kurang ' . $mk->sum(fn ($m) => max(0, -$m->gap)) . ' dosen'],
        ['Dosen di matriks', $dosen->count(), 'people', 'rata-rata ' . ($dosen->count() ? round($perDosen->avg(), 1) : 0) . ' MK/dosen'],
    ] as [$judul, $nilai, $ikon, $ket])
        <div class="col"><div class="card kartu h-100"><div class="card-body d-flex gap-3 align-items-center">
            <div class="ikon flex-shrink-0"><i class="bi bi-{{ $ikon }}"></i></div>
            <div><div class="small text-muted">{{ $judul }}</div><div class="fs-4 fw-semibold lh-1">{{ $nilai }}</div><div class="small text-muted">{{ $ket }}</div></div>
        </div></div></div>
    @endforeach
</div>

{{-- Matriks MK × dosen --}}
<form method="post" action="{{ route('dashboard.dosen.mk.simpan') }}" class="card mb-3">
    @csrf <input type="hidden" name="prodi" value="{{ $prodi }}"><input type="hidden" name="kurikulum" value="{{ $kurikulum }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Distribusi Mata Kuliah Inti ke Dosen <small class="text-muted">· {{ $prodi }}, kurikulum {{ $kurikulum }}</small>
            <br><small class="text-muted">@if ($dipakaiAngkatan->isNotEmpty())Dipakai {{ $dipakaiAngkatan->count() }} angkatan aktif (masuk {{ $dipakaiAngkatan->first() }}{{ $dipakaiAngkatan->count() > 1 ? ' – ' . $dipakaiAngkatan->last() : '' }})@else Tidak dipakai angkatan aktif @endif</small></span>
        @if ($ubah)<button class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>@endif
    </div>
    <div class="table-responsive" style="max-height:640px">
    <table class="table table-sm table-bordered small mb-0 align-middle">
        <thead class="table-light sticky-top">
            <tr>
                <th>#</th><th style="min-width:200px">Mata Kuliah Inti</th><th>Smt</th>
                @foreach ($dosen as $d)<th class="text-center" title="{{ $d->nama }}{{ $d->aktif ? '' : ' (nonaktif)' }}" style="writing-mode:vertical-rl;transform:rotate(180deg);white-space:nowrap">{{ $singkat[$d->id] }}{{ $d->aktif ? '' : ' *' }}</th>@endforeach
                <th class="text-center">Jml</th><th class="text-center">Butuh</th><th class="text-center">GAP</th><th>Solusi / Catatan</th>@if ($ubah)<th></th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach ($mk as $i => $m)
            <tr class="{{ $m->gap < 0 ? 'table-warning' : '' }}">
                <td class="text-muted">{{ $i + 1 }}</td>
                <td>{{ $m->nama }}
                    @if ($m->kode_mk)<span class="badge text-bg-light" title="Kode MK di kurikulum eUniversity">{{ $m->kode_mk }}{{ $m->sks ? " · {$m->sks} SKS" : '' }}</span>
                    @elseif ($m->id_kurikulum_api)<span class="badge text-bg-warning" title="Nama tidak ditemukan di kurikulum eUniversity">belum tertaut</span>@endif
                    <br><small class="text-muted">{{ $m->bidang_ilmu }}{{ $m->irisan ? ' · ' . $m->irisan : '' }}</small>
                    @if ($ubah && $materiApi->isNotEmpty())
                        @php($pilih = $m->kode_mk ?? $saran[$m->id] ?? null)
                        <select name="kode[{{ $m->id }}]" class="form-select form-select-sm mt-1 {{ !$m->kode_mk && $pilih ? 'border-warning' : '' }}" style="max-width:320px">
                            <option value="">— tautkan ke MK eUniversity —</option>
                            @foreach ($materiApi as $kode => $label)<option value="{{ $kode }}" @selected($pilih === $kode)>{{ $label }}</option>@endforeach
                        </select>
                        @if (!$m->kode_mk && $pilih)<small class="text-warning">Saran otomatis, periksa lalu Simpan</small>@endif
                    @endif</td>
                <td>{{ $m->semester }}</td>
                @foreach ($dosen as $d)
                    @php($ya = $m->dosen->contains('id', $d->id))
                    @php($ajar = $pernah($m, $d))
                    <td class="text-center {{ $ya ? 'bg-success-subtle' : ($ajar ? 'bg-info-subtle' : '') }}" title="{{ $ajar ? 'Pernah mengampu (eUniversity)' : '' }}">
                        @if ($ubah)<input type="checkbox" name="kuasai[{{ $m->id }}][{{ $d->id }}]" value="1" class="form-check-input" @checked($ya)>
                        @elseif ($ya)<i class="bi bi-check-lg text-success"></i>@endif
                        @if ($ajar)<i class="bi bi-easel2 text-info small"></i>@endif
                    </td>
                @endforeach
                <td class="text-center fw-semibold" title="{{ $m->dosen->count() }} dicentang HRD + {{ count($m->kuasai) - $m->dosen->count() }} dari eUniversity">{{ count($m->kuasai) }}</td>
                <td class="text-center">@if ($ubah)<input name="kebutuhan[{{ $m->id }}]" type="number" min="0" max="20" value="{{ $m->kebutuhan }}" class="form-control form-control-sm" style="width:60px">@else{{ $m->kebutuhan }}@endif</td>
                <td class="text-center"><span class="badge text-bg-{{ $m->gap < 0 ? 'danger' : 'success' }}">{{ $m->gap > 0 ? '+' : '' }}{{ $m->gap }}</span></td>
                <td>@if ($ubah)<input name="solusi[{{ $m->id }}]" value="{{ $m->solusi }}" class="form-control form-control-sm">@else<small>{{ $m->solusi }}</small>@endif</td>
                @if ($ubah)<td><button type="submit" form="hapus{{ $m->id }}" class="btn btn-sm btn-outline-danger" title="Hapus MK"><i class="bi bi-x"></i></button></td>@endif
            </tr>
        @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr><td colspan="3" class="text-end small">MK dikuasai</td>
                @foreach ($dosen as $d)<td class="text-center fw-semibold">{{ $perDosen[$d->id] }}</td>@endforeach
                <td colspan="{{ $ubah ? 5 : 4 }}"></td></tr>
        </tfoot>
    </table>
    </div>
    <div class="card-footer small text-muted">GAP = jumlah dosen yang menguasai − kebutuhan. Baris kuning = belum tercapai. * = dosen nonaktif.
        <i class="bi bi-easel2 text-info"></i> sel biru = pernah mengampu MK ini di eUniversity (6 periode terakhir) dan otomatis dihitung menguasai; centang = penilaian HRD.</div>
</form>
@if ($ubah)
    @foreach ($mk as $m)
        <form id="hapus{{ $m->id }}" method="post" action="{{ route('dashboard.dosen.mk.hapus', $m) }}" onsubmit="return confirm('Hapus {{ $m->nama }} dari kurikulum ini?')">@csrf @method('delete')</form>
    @endforeach
@endif
@endif

<div class="row g-3">
    @if ($ubah || ($bolehUbah && !$kurikulum))
    <div class="col-lg-6"><form method="post" action="{{ route('dashboard.dosen.mk.tambah') }}" class="card h-100"><div class="card-body">
        @csrf <input type="hidden" name="program_studi" value="{{ $prodi }}">
        <h6>Tambah Mata Kuliah Inti</h6>
        <div class="row g-2">
            @php($sudah = $mk->pluck('kode_mk')->filter()->all())
            @if ($materiApi->isNotEmpty())
                {{-- Kurikulum sudah tertaut: pilih dari MK eUniversity (nama, SKS, semester ikut API) --}}
                <div class="col-md-8"><label class="form-label small mb-0">MK dari kurikulum eUniversity</label>
                    <select name="kode_mk" class="form-select form-select-sm" required>
                        <option value="">Pilih…</option>
                        @foreach ($materiApi->except($sudah) as $kode => $label)<option value="{{ $kode }}">{{ $label }}</option>@endforeach
                    </select></div>
            @else
                <div class="col-md-8"><label class="form-label small mb-0">Nama MK</label><input name="nama" class="form-control form-control-sm" required></div>
            @endif
            <div class="col-md-4"><label class="form-label small mb-0">Kurikulum</label><input name="kurikulum" value="{{ $kurikulum ?? now()->year }}" class="form-control form-control-sm" required></div>
            <div class="col-md-6"><label class="form-label small mb-0">Bidang ilmu</label><input name="bidang_ilmu" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label small mb-0">Semester</label><select name="semester" class="form-select form-select-sm"><option></option><option>Ganjil</option><option>Genap</option></select></div>
            <div class="col-md-3"><label class="form-label small mb-0">Butuh dosen</label><input name="kebutuhan" type="number" value="2" min="0" class="form-control form-control-sm"></div>
            <div class="col-md-9"><label class="form-label small mb-0">Irisan prodi</label><input name="irisan" placeholder="mis. SI, IF, BD" class="form-control form-control-sm"></div>
            <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Tambah</button></div>
        </div>
    </div></form></div>
    @endif

    {{-- Kekurangan dosen per MK, semua prodi (kurikulum terbaru) --}}
    <div class="col-lg-6"><div class="card h-100">
        <div class="card-header">Kekurangan Dosen per Mata Kuliah <small class="text-muted">· kurikulum terbaru tiap prodi</small></div>
        <ul class="list-group list-group-flush small" style="max-height:320px;overflow:auto">
            @foreach ($kurangSemua as $p => $daftar)
                <li class="list-group-item bg-light fw-semibold">{{ $p }} <span class="badge text-bg-{{ $daftar->isEmpty() ? 'success' : 'warning' }}">{{ $daftar->count() }} MK</span></li>
                @foreach ($daftar as $m)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $m->nama }}</span><span class="text-danger">kurang {{ -$m->gap }}</span></li>
                @endforeach
            @endforeach
        </ul>
    </div></div>
</div>
@endsection
