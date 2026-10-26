@extends('layout')
@section('title', 'Rapor · ' . $d->nama)
@section('breadcrumb')
    @if (Gate::allows('admin') || request()->routeIs('dashboard.dosen.rapor'))
        <li class="breadcrumb-item"><a href="{{ route('dashboard.dosen.rapor.index') }}" class="text-decoration-none">Rapor Dosen</a></li>
    @endif
@endsection

@php
    $J = \App\Http\Controllers\DashboardDosenController::class;
    $C = \App\Models\CapaianDosen::class;
    $D = \App\Models\Disc::class;
    $tgl = fn ($v) => $v?->translatedFormat('d M Y') ?? '-';
    $sertifikat = $capaian['sertifikat'] ?? collect();
@endphp

@section('actions')
    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</button>
@endsection

@section('content')
<div class="row g-3">
    {{-- Profil & kualifikasi --}}
    <div class="col-xl-4 d-flex flex-column gap-3">
        <div class="card">
            <div class="card-header">Profil Dosen</div>
            <table class="table table-sm small mb-0">
                <tr><th style="width:42%">NIDN</th><td>{{ $d->nidn ?? '-' }}</td></tr>
                <tr><th>Program Studi</th><td>{{ $d->program_studi ?? '-' }}</td></tr>
                <tr><th>TMT Dosen</th><td>{{ $tgl($d->tmt_dosen ?? $d->mulai_kerja) }} <span class="text-muted">({{ $nilai->masa ?? '-' }} th)</span></td></tr>
                <tr><th>Jabatan Fungsional</th><td>{{ $d->jabatan_fungsional ?? 'Non JFA' }}{{ $d->angka_kredit ? " ({$d->angka_kredit})" : '' }}<br><small class="text-muted">TMT {{ $tgl($d->tmt_jabfung) }}</small></td></tr>
                <tr><th>Jafung seharusnya</th><td>@if ($nilai->seharusnya !== null){{ $J::JAFUNG[$nilai->seharusnya] }} <span class="badge text-bg-{{ $nilai->tepat ? 'success' : 'warning' }}">{{ $nilai->tepat ? 'Tepat' : 'Tertinggal' }}</span>@else - @endif</td></tr>
                <tr><th>Naik berikutnya</th><td>@if ($nilai->jenjang < 4){{ $J::JAFUNG[min($nilai->jenjang + 1, 4)] }} · {{ $nilai->layakNaik ? ($nilai->bisaNaik ? 'sudah bisa diajukan' : 'mulai ' . $nilai->layakNaik->translatedFormat('M Y')) : 'TMT jafung belum diisi' }}@endif</td></tr>
                <tr><th>Bimbingan Akademik (PA)</th><td>{{ $d->jumlah_pa !== null ? $d->jumlah_pa . ' mahasiswa' : '-' }}
                    @if ($pa && $pa['status'])<div class="text-muted">{{ collect($pa['status'])->map(fn ($n, $s) => "$s $n")->join(' · ') }}</div>@endif</td></tr>
                <tr><th>Dosen Penguji</th><td>{!! $d->penguji ? '<span class="badge text-bg-success">Terdaftar</span>' : '<span class="text-muted">Belum terdaftar</span>' !!}</td></tr>
                <tr><th>Sertifikasi Dosen</th><td>@if ($d->serdos)<span class="badge text-bg-success">Serdos</span>@elseif ($nilai->eligibleSerdos)<span class="badge text-bg-info">Eligible</span>@else<span class="badge text-bg-light">Belum</span>@endif</td></tr>
                <tr><th>Pendidikan</th><td>
                    @forelse ($pendidikan->sortBy(fn ($p) => array_search($p->jenjang, \App\Models\RiwayatPendidikan::JENJANG)) as $p)
                        <div><strong>{{ $p->jenjang }}</strong> {{ $p->program_studi }} – {{ $p->institusi }} <span class="text-muted">({{ $p->tahun_lulus ?? $p->status }})</span></div>
                    @empty {{ $d->pendidikan_terakhir ?? '-' }} @endforelse</td></tr>
            </table>
        </div>

        <div class="card">
            <div class="card-header">Peta Karakter (DISC)</div>
            <div class="card-body small">
                @if ($disc)
                    <div class="mb-2">Profil <span class="badge text-bg-light fs-6">{{ $disc->profil ?? '-' }}</span> <span class="text-muted">{{ $disc->tim }}{{ $disc->dinilai_at ? ' · ' . $disc->dinilai_at->translatedFormat('M Y') : '' }}</span></div>
                    @foreach (['Komitmen' => [$disc->komitmen, $D::KOMITMEN], 'Kompetensi' => [$disc->kompetensi, $D::KOMPETENSI]] as $judul => [$nilaiD, $kolom])
                        @php($kat = $D::kategori($nilaiD))
                        <div class="d-flex justify-content-between mt-2"><strong>{{ $judul }}</strong><span>{{ $nilaiD ?? '-' }} @if ($kat)<span class="badge text-bg-secondary">{{ $kat }} · {{ $D::GAYA[$kat] }}</span>@endif</span></div>
                        @foreach ($kolom as $k => $l)
                            <div class="batang" style="grid-template-columns: 100px 1fr 24px"><div>{{ $l }}</div><div class="batang-jalur"><div class="batang-isi" style="width:{{ ($disc->$k ?? 0) / 4 * 100 }}%"></div></div><div class="batang-nilai">{{ $disc->$k ?? '·' }}</div></div>
                        @endforeach
                    @endforeach
                @else
                    <span class="text-muted">Belum dipetakan.</span>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">Kompetensi {{ $tahunKompetensi }}</div>
            <div class="card-body small">
                @forelse ($kompetensi as $kel => $daftar)
                    <div class="fw-semibold mt-1">{{ \App\Models\Kompetensi::KELOMPOK[$kel] }}</div>
                    @foreach ($daftar as $k)
                        <div class="batang" style="grid-template-columns: minmax(110px, 45%) 1fr 40px" title="{{ $k->rencana }}">
                            <div class="text-truncate">{{ $k->skill }}</div>
                            <div class="batang-jalur position-relative"><div class="batang-isi" style="width:{{ $k->sekarang / 5 * 100 }}%"></div>
                                @if ($k->target)<div class="position-absolute top-0 bottom-0 border-end border-2 border-danger" style="left:{{ $k->target / 5 * 100 }}%"></div>@endif</div>
                            <div class="batang-nilai">{{ $k->sekarang }}/{{ $k->target ?? '-' }}</div></div>
                    @endforeach
                @empty
                    <span class="text-muted">Belum dinilai.</span>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">Kehadiran {{ $tahunHadir }}</div>
            <div class="card-body small">
                @if ($hadir->isNotEmpty())
                    <div>Terlambat <strong>{{ $hadir->sum('terlambat_menit') }}</strong> menit dalam {{ $hadir->whereNotNull('terlambat_menit')->count() }} bulan
                        (rata-rata {{ round($hadir->avg('terlambat_menit')) }} menit/bulan)</div>
                    <div>Tidak hadir <strong>{{ $hadir->sum('tidak_hadir_hari') + 0 }}</strong> hari</div>
                @else <span class="text-muted">Belum ada data.</span> @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8 d-flex flex-column gap-3">
        {{-- Roadmap karier --}}
        @php($roadmap = ($capaian['roadmap'] ?? collect())->sortBy(fn ($x) => sprintf('%04d%02d', (int) $x->tahun, $x->bulan ?? 0)))
        <div class="card" id="roadmap">
            <div class="card-header">Roadmap Karier</div>
            <ul class="list-group list-group-flush small">
                @forelse ($roadmap as $x)
                    @php($lewat = (int) $x->tahun < now()->year || ((int) $x->tahun === now()->year && $x->bulan && $x->bulan < now()->month))
                    <li class="list-group-item d-flex gap-3 align-items-start">
                        <div class="text-nowrap text-muted" style="width:96px">{{ $x->bulan ? $C::BULAN[$x->bulan] : '' }} {{ $x->tahun }}</div>
                        <div class="flex-grow-1">{{ $x->judul }}@if ($x->sumber) <span class="text-muted">· {{ $x->sumber }}</span>@endif
                            @if ($x->keterangan)<div class="text-muted">{{ $x->keterangan }}</div>@endif</div>
                        <div class="text-nowrap">
                            @if ($x->status)<span class="badge text-bg-{{ $x->status === 'Tercapai' ? 'success' : 'danger' }}">{{ $x->status }}</span>
                            @elseif ($lewat)<span class="badge text-bg-warning">Belum dikonfirmasi</span>
                            @else<span class="badge text-bg-light">Rencana</span>@endif
                            @if ($bolehUbah)
                                <form method="post" action="{{ route('dashboard.dosen.capaian.status', $x) }}" class="d-inline">@csrf @method('patch')
                                    <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()" title="Ubah status">
                                        <option value="">–</option>@foreach (['Tercapai', 'Tidak Tercapai'] as $s)<option @selected($x->status === $s)>{{ $s }}</option>@endforeach
                                    </select></form>
                                <form method="post" action="{{ route('dashboard.dosen.capaian.hapus', $x) }}" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('delete')<button class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bi bi-x-lg"></i></button></form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Belum ada roadmap.</li>
                @endforelse
            </ul>
        </div>

        {{-- Sertifikat, prestasi, publikasi, rekognisi --}}
        @foreach (['sertifikat', 'prestasi', 'publikasi', 'rekognisi'] as $jenis)
            @php($daftar = ($capaian[$jenis] ?? collect())->sortByDesc('tahun'))
            <div class="card" id="{{ $jenis }}">
                <div class="card-header d-flex justify-content-between"><span>{{ $C::JENIS[$jenis] }} <span class="badge text-bg-light">{{ $daftar->count() }}</span></span>
                    @if ($jenis === 'sertifikat')<small class="text-muted">{{ $daftar->filter->kompetensi->count() }} sertifikat kompetensi (BNSP/LSP)</small>@endif</div>
                <div class="table-responsive" style="max-height:300px">
                <table class="table table-sm small mb-0 align-middle">
                    @forelse ($daftar as $x)
                        <tr>
                            <td class="text-nowrap text-muted" style="width:96px">{{ $x->tahun }}</td>
                            <td>{{ $x->judul }} @if ($x->kompetensi)<span class="badge text-bg-success">Kompetensi</span>@endif
                                @if ($x->sumber || $x->keterangan)<div class="text-muted">{{ collect([$x->sumber, $x->keterangan])->filter()->join(' · ') }}</div>@endif</td>
                            <td class="text-nowrap">@if ($x->status)<span class="badge text-bg-light">{{ $x->status }}</span>@endif</td>
                            <td class="text-end text-nowrap">@if ($x->link)<a href="{{ $x->link }}" target="_blank" rel="noopener" title="Buka tautan"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                                @if ($bolehUbah)<form method="post" action="{{ route('dashboard.dosen.capaian.hapus', $x) }}" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('delete')<button class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bi bi-x-lg"></i></button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">Belum ada data.</td></tr>
                    @endforelse
                </table>
                </div>
            </div>
        @endforeach

        {{-- Bimbingan PKL / proposal / skripsi (eUniversity) --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between"><span>Bimbingan PKL / Proposal / Skripsi <small class="text-muted">· eUniversity</small></span>
                <small class="text-muted">@foreach (\App\Models\Bimbingan::JENIS as $j => $l){{ $l }} {{ $bimbingan->flatten()->where('jenis', $j)->count() }}{{ $loop->last ? '' : ' · ' }}@endforeach</small></div>
            <div class="table-responsive" style="max-height:300px">
            <table class="table table-sm small mb-0">
                @forelse ($bimbingan as $periode => $daftar)
                    <tr class="table-light"><th colspan="3">{{ $periode }} <span class="text-muted fw-normal">· {{ $daftar->countBy('jenis')->map(fn ($n, $j) => \App\Models\Bimbingan::JENIS[$j] . " $n")->join(', ') }}</span></th></tr>
                    @foreach ($daftar as $b)
                        <tr><td style="width:80px"><span class="badge text-bg-light">{{ \App\Models\Bimbingan::JENIS[$b->jenis] }}</span></td><td>{{ $b->judul ?? '-' }}</td><td class="text-muted text-nowrap">{{ $b->program_studi }}</td></tr>
                    @endforeach
                @empty
                    <tr><td class="text-muted">Belum ada bimbingan PKL/skripsi di eUniversity.</td></tr>
                @endforelse
            </table>
            </div>
            @if ($pa && $pa['angkatan'])
                <div class="card-footer small text-muted">Bimbingan PA per angkatan: {{ collect($pa['angkatan'])->map(fn ($n, $a) => "$a: $n")->join(' · ') }}</div>
            @endif
        </div>

        {{-- Kelas yang diampu (eUniversity) --}}
        <div class="card">
            <div class="card-header">Mata Kuliah yang Diampu <small class="text-muted">· eUniversity</small></div>
            <div class="table-responsive" style="max-height:300px">
            <table class="table table-sm small mb-0">
                @forelse ($diampu as $periode => $kelas)
                    <tr class="table-light"><th colspan="3">{{ $periode }} <span class="text-muted fw-normal">· {{ $kelas->unique('kode_mk')->count() }} MK, {{ $kelas->unique('kode_mk')->sum('sks') }} SKS</span></th></tr>
                    @foreach ($kelas->groupBy('kode_mk') as $k)
                        <tr><td>{{ $k->first()->nama_mk }} <span class="text-muted">{{ $k->first()->kode_mk }}</span></td>
                            <td class="text-muted">{{ $k->pluck('kelas')->join(', ') }}</td>
                            <td class="text-end text-nowrap">{{ $k->first()->sks }} SKS · {{ $k->sum('jumlah_mhs') }} mhs</td></tr>
                    @endforeach
                @empty
                    <tr><td class="text-muted">Belum ada data kelas di eUniversity.</td></tr>
                @endforelse
            </table>
            </div>
        </div>

        {{-- Penguasaan mata kuliah --}}
        <div class="card">
            <div class="card-header">Mata Kuliah Inti yang Dikuasai</div>
            <div class="card-body small">
                @forelse ($mk as $grup => $daftar)
                    <div class="fw-semibold">{{ $grup }} <span class="text-muted fw-normal">({{ $daftar->count() }})</span></div>
                    <div class="mb-2">@foreach ($daftar as $m)<span class="badge text-bg-light me-1 mb-1">{{ $m->nama }}</span>@endforeach</div>
                @empty <span class="text-muted">Belum ada.</span> @endforelse
            </div>
        </div>

        @if ($bolehUbah)
            <form method="post" action="{{ route('dashboard.dosen.rapor.simpan', $d) }}" class="card d-print-none"><div class="card-body">
                @csrf
                <h6>Tambah Isian Rapor</h6>
                <div class="row g-2 small">
                    <div class="col-md-3"><label class="form-label mb-0">Jenis</label><select name="jenis" class="form-select form-select-sm">@foreach ($C::JENIS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label mb-0">Judul / rencana *</label><input name="judul" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label mb-0">Tahun</label><input name="tahun" class="form-control form-control-sm" placeholder="{{ now()->year }}"></div>
                    <div class="col-md-1"><label class="form-label mb-0">Bulan</label><input name="bulan" type="number" min="1" max="12" class="form-control form-control-sm"></div>
                    <div class="col-md-4"><label class="form-label mb-0">Penyelenggara / penerbit</label><input name="sumber" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label mb-0">Status / peringkat / jenis</label><input name="status" class="form-control form-control-sm" placeholder="mis. Sinta 2, HKI, Tercapai"></div>
                    <div class="col-md-5"><label class="form-label mb-0">Tautan</label><input name="link" type="url" class="form-control form-control-sm" placeholder="https://"></div>
                    <div class="col-md-10"><label class="form-label mb-0">Keterangan</label><input name="keterangan" class="form-control form-control-sm"></div>
                    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Tambah</button></div>
                </div>
            </div></form>
        @endif
    </div>
</div>
@endsection
