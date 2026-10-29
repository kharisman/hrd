@extends('layout')
@section('title', 'Kompetensi Dosen')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.dosen') }}" class="text-decoration-none">Dashboard Dosen</a></li>
@endsection

@php
    $K = \App\Models\Kompetensi::class;
    // Batang skor 0–5 dengan penanda target
    $skor = fn ($v, $target = null) => $v === null ? '<span class="text-muted">·</span>'
        : '<div class="d-flex align-items-center gap-2"><div class="batang-jalur flex-grow-1 position-relative" style="min-width:70px"><div class="batang-isi" style="width:' . ($v / 5 * 100) . '%"></div>'
          . ($target ? '<div class="position-absolute top-0 bottom-0 border-end border-2 border-danger" style="left:' . ($target / 5 * 100) . '%"></div>' : '') . '</div><span class="batang-nilai" style="width:32px">' . $v . '</span></div>';
    $naik = fn ($now, $lalu) => $now !== null && $lalu !== null && $now != $lalu
        ? '<small class="' . ($now > $lalu ? 'text-success' : 'text-danger') . '">' . ($now > $lalu ? '▲' : '▼') . abs(round($now - $lalu, 2)) . '</small>' : '';
@endphp

@section('content')
@include('dashboard._tab-dosen')

<div class="d-flex flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills small">
        @if (\App\Support\Akses::semuaProdi())<li class="nav-item"><a class="nav-link {{ !$prodi ? 'active' : '' }}" href="{{ route('dashboard.dosen.kompetensi', ['tahun' => $tahun]) }}">Semua Prodi</a></li>@endif
        @foreach (\App\Support\Akses::prodiDosen() as $p)
            <li class="nav-item"><a class="nav-link {{ $prodi === $p ? 'active' : '' }}" href="{{ route('dashboard.dosen.kompetensi', ['prodi' => $p, 'tahun' => $tahun]) }}">{{ $p }}</a></li>
        @endforeach
    </ul>
    <div class="btn-group btn-group-sm ms-auto">
        @foreach ($daftarTahun as $t)
            <a href="{{ route('dashboard.dosen.kompetensi', ['prodi' => $prodi, 'tahun' => $t]) }}" class="btn btn-outline-secondary {{ $t == $tahun ? 'active' : '' }}">{{ $t }}</a>
        @endforeach
    </div>
</div>

@if ($edit)
    {{-- Form ubah kompetensi satu dosen --}}
    @php($ada = $edit->skill->map(fn ($s) => $s->kelompok . '|' . $s->skill))
    @php($isi = $edit->skill->concat(collect($K::BAKU)->flatMap(fn ($daftar, $k) => collect($daftar)->reject(fn ($n) => $ada->contains("$k|$n"))->map(fn ($n) => new $K(['kelompok' => $k, 'skill' => $n])))))
    <form method="post" action="{{ route('dashboard.dosen.kompetensi.simpan', $edit->model) }}" class="card mb-3">
        @csrf <input type="hidden" name="tahun" value="{{ $tahun }}">
        <div class="card-header d-flex justify-content-between"><strong>Ubah Kompetensi · {{ $edit->model->nama }} · {{ $tahun }}</strong>
            <a href="{{ route('dashboard.dosen.kompetensi', ['prodi' => $prodi, 'tahun' => $tahun]) }}" class="small">Batal</a></div>
        <div class="table-responsive">
        <table class="table table-sm small mb-0 align-middle">
            <thead class="table-light"><tr><th>Kelompok</th><th>Skill</th><th style="width:90px">Sekarang</th><th style="width:90px">Target</th><th>Rencana aksi</th><th>Catatan</th></tr></thead>
            <tbody id="barisSkill">
            @foreach ($isi->sortBy(fn ($s) => $s->kelompok)->values() as $i => $s)
                <tr>
                    <td><select name="skill[{{ $i }}][kelompok]" class="form-select form-select-sm">@foreach ($K::KELOMPOK as $k => $l)<option value="{{ $k }}" @selected($s->kelompok === $k)>{{ $l }}</option>@endforeach</select></td>
                    <td><input name="skill[{{ $i }}][nama]" value="{{ $s->skill }}" class="form-control form-control-sm" placeholder="kosongkan = hapus"></td>
                    <td><input name="skill[{{ $i }}][sekarang]" type="number" min="1" max="5" value="{{ $s->sekarang }}" class="form-control form-control-sm"></td>
                    <td><input name="skill[{{ $i }}][target]" type="number" min="1" max="5" value="{{ $s->target }}" class="form-control form-control-sm"></td>
                    <td><input name="skill[{{ $i }}][rencana]" value="{{ $s->rencana }}" class="form-control form-control-sm"></td>
                    <td><input name="skill[{{ $i }}][catatan]" value="{{ $s->catatan }}" class="form-control form-control-sm"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahSkill()"><i class="bi bi-plus-lg me-1"></i>Skill lain</button>
            <button class="btn btn-sm btn-primary ms-auto">Simpan</button>
        </div>
    </form>
    <script>
        let nSkill = {{ $isi->count() }};
        function tambahSkill() {
            const b = document.querySelector('#barisSkill tr:last-child').cloneNode(true);
            b.querySelectorAll('input, select').forEach(e => { e.name = e.name.replace(/skill\[\d+\]/, 'skill[' + nSkill + ']'); if (e.tagName === 'INPUT') e.value = ''; });
            document.getElementById('barisSkill').appendChild(b); nSkill++;
        }
    </script>
@endif

<div class="row g-3">
    <div class="col-xl-8"><div class="card h-100">
        <div class="card-header">Nilai Kompetensi per Dosen {{ $tahun }} <small class="text-muted">· rata-rata skala 1–5, garis merah = target</small></div>
        <div class="table-responsive">
        <table class="table table-sm small mb-0 align-middle">
            <thead class="table-light"><tr><th>Dosen</th>@unless ($prodi)<th>Prodi</th>@endunless<th style="width:30%">Personal</th><th style="width:30%">Jabatan</th>@if ($bolehUbah)<th></th>@endif</tr></thead>
            @foreach ($baris as $b)
                <tr class="{{ $edit && $edit->model->id === $b->model->id ? 'table-primary' : '' }}">
                    <td>{{ $b->model->nama }}</td>
                    @unless ($prodi)<td class="text-muted">{{ $b->model->program_studi }}</td>@endunless
                    <td>{!! $skor($b->personal, $b->targetPersonal) !!} {!! $naik($b->personal, $b->personalLalu) !!}</td>
                    <td>{!! $skor($b->jabatan, $b->targetJabatan) !!} {!! $naik($b->jabatan, $b->jabatanLalu) !!}</td>
                    @if ($bolehUbah)<td><a href="{{ route('dashboard.dosen.kompetensi', ['prodi' => $prodi, 'tahun' => $tahun, 'dosen' => $b->model->id]) }}" class="btn btn-sm btn-outline-secondary" title="Ubah"><i class="bi bi-pencil"></i></a></td>@endif
                </tr>
            @endforeach
        </table>
        </div>
        <div class="card-footer small text-muted">▲▼ = perubahan dibanding {{ $tahun - 1 }}. Dosen tanpa nilai belum dinilai tahun ini.</div>
    </div></div>

    <div class="col-xl-4"><div class="card h-100">
        <div class="card-header">Rata-rata per Skill</div>
        <div class="card-body">
            @forelse ($perSkill->sortBy('sekarang') as $kunci => $s)
                @php([$kel, $nama] = explode('|', $kunci, 2))
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span>{{ $nama }} <span class="text-muted">· {{ $kel }}</span></span><span class="text-muted">{{ $s['n'] }} dosen</span></div>
                    {!! $skor($s['sekarang'], $s['target']) !!}
                </div>
            @empty
                <div class="text-muted small">Belum ada penilaian kompetensi tahun {{ $tahun }}.</div>
            @endforelse
            <div class="small text-muted mt-2">Diurutkan dari yang terendah: kandidat pelatihan bersama.</div>
        </div>
    </div></div>
</div>
@endsection
