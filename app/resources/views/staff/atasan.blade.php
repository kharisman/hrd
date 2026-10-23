@extends('layout')
@section('title', 'Atasan & Bawahan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('staff.index') }}" class="text-decoration-none">Data Pegawai</a></li>
@endsection

@section('content')
{{-- 1. Pilih atasan --}}
<form class="card mb-3"><div class="card-body">
    <label class="form-label fw-semibold" for="cariAtasan">1. Pilih atasan</label>
    <div class="input-group" style="max-width:720px">
        <input class="form-control" list="daftarAtasan" id="cariAtasan" placeholder="Ketik nama atasan..." autocomplete="off"
               value="{{ $atasan ? $atasan->nama . ' — ' . $atasan->level . ', ' . $atasan->divisi . ' #' . $atasan->id : '' }}">
        <button class="btn btn-primary"><i class="bi bi-person-check me-1"></i>Tampilkan</button>
    </div>
    <datalist id="daftarAtasan">
        @foreach ($semua as $s)<option value="{{ $s->nama }} — {{ $s->level }}, {{ $s->divisi }} #{{ $s->id }}">{{ $s->bawahan_count ? $s->bawahan_count . ' bawahan' : '' }}</option>@endforeach
    </datalist>
    <input type="hidden" name="atasan" id="idAtasan" value="{{ $atasan?->id }}">
    <div class="form-text"><i class="bi bi-info-circle me-1"></i>{{ $tanpaAtasan }} pegawai aktif belum punya atasan. Hanya pegawai aktif yang tampil sebagai calon atasan & bawahan. Atasan inilah yang menyetujui cuti bawahannya.</div>
</div></form>

@if ($atasan)
<div class="row g-3">
    {{-- 2. Bawahan saat ini --}}
    <div class="col-lg-5">
        <form method="post" action="{{ route('staff.atasan.simpan') }}" class="card h-100">
            @csrf
            <input type="hidden" name="atasan_id" value="">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong>2. Bawahan {{ $atasan->nama }}</strong> <span class="badge text-bg-primary">{{ $bawahan->count() }}</span></span>
                <a href="{{ route('staff.show', $atasan) }}" class="small text-decoration-none">Profil</a>
            </div>
            <div class="list-group list-group-flush overflow-auto" style="max-height:60vh">
                @forelse ($bawahan as $b)
                    <label class="list-group-item d-flex gap-2 align-items-start">
                        <input type="checkbox" name="pegawai[]" value="{{ $b->id }}" class="form-check-input mt-1 pilih-lepas" onchange="hitung('lepas')">
                        <span class="flex-grow-1 {{ $b->aktif ? '' : 'text-muted' }}">{{ $b->nama }}
                            @unless ($b->aktif)<span class="badge text-bg-secondary">Nonaktif</span>@endunless
                            <br><small class="text-muted">{{ $b->level }} · {{ $b->divisi }}</small></span>
                    </label>
                @empty
                    <div class="list-group-item text-muted small">Belum ada bawahan. Tambahkan dari panel sebelah.</div>
                @endforelse
            </div>
            @if ($bawahan->isNotEmpty())
            <div class="card-footer bg-white d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input" title="Pilih semua" onchange="pilihSemua('lepas', this.checked)">
                <span class="small text-muted flex-grow-1"><span id="jml-lepas">0</span> dipilih</span>
                <button class="btn btn-sm btn-outline-danger" id="btn-lepas" disabled onclick="return confirm('Lepaskan bawahan yang dipilih dari {{ e($atasan->nama) }}?')"><i class="bi bi-person-dash me-1"></i>Lepaskan</button>
            </div>
            @endif
        </form>
    </div>

    {{-- 3. Tambah bawahan --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><strong>3. Tambah bawahan</strong></div>
            <form class="bar-filter rounded-0">
                <input type="hidden" name="atasan" value="{{ $atasan->id }}">
                <select name="divisi" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua divisi</option>
                    @foreach ($divisiList as $id => $nama)<option value="{{ $id }}" @selected(request('divisi') == $id)>{{ $nama }}</option>@endforeach
                </select>
                <select name="level" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua level</option>
                    @foreach ($levelList as $id => $nama)<option value="{{ $id }}" @selected(request('level') == $id)>{{ $nama }}</option>@endforeach
                </select>
                <div class="input-group">
                    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / NIP">
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="kosong" value="1" id="kosong" @checked(request()->boolean('kosong')) onchange="this.form.submit()">
                    <label class="form-check-label small" for="kosong">Hanya yang belum punya atasan</label>
                </div>
            </form>
            <form method="post" action="{{ route('staff.atasan.simpan') }}">
                @csrf
                <input type="hidden" name="atasan_id" value="{{ $atasan->id }}">
                <div class="list-group list-group-flush overflow-auto" style="max-height:50vh">
                    @forelse ($kandidat as $k)
                        <label class="list-group-item d-flex gap-2 align-items-start">
                            <input type="checkbox" name="pegawai[]" value="{{ $k->id }}" class="form-check-input mt-1 pilih-tambah" onchange="hitung('tambah')">
                            <span class="flex-grow-1">{{ $k->nama }}<br><small class="text-muted">{{ $k->level }} · {{ $k->divisi }}</small></span>
                            <small class="text-nowrap {{ $k->atasan ? 'text-muted' : 'text-warning' }}">{{ $k->atasan ? 'Atasan: ' . $k->atasan->nama : 'Belum ada atasan' }}</small>
                        </label>
                    @empty
                        <div class="list-group-item text-muted small">Tidak ada pegawai yang cocok dengan filter.</div>
                    @endforelse
                </div>
                <div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2">
                    <input type="checkbox" class="form-check-input" title="Pilih semua di halaman ini" onchange="pilihSemua('tambah', this.checked)">
                    <span class="small text-muted flex-grow-1"><span id="jml-tambah">0</span> dipilih · {{ $kandidat->total() }} pegawai</span>
                    <button class="btn btn-sm btn-primary" id="btn-tambah" disabled><i class="bi bi-person-plus me-1"></i>Jadikan bawahan</button>
                </div>
            </form>
        </div>
        <div class="mt-2">{{ $kandidat->links() }}</div>
    </div>
</div>
@endif

<script>
    // Pilih atasan dari datalist → isi id tersembunyi (format "... #id")
    document.getElementById('cariAtasan').addEventListener('input', e => {
        const m = e.target.value.match(/#(\d+)$/);
        document.getElementById('idAtasan').value = m ? m[1] : '';
        if (m) e.target.form.submit();
    });
    function hitung(jenis) {
        const n = document.querySelectorAll('.pilih-' + jenis + ':checked').length;
        document.getElementById('jml-' + jenis).textContent = n;
        document.getElementById('btn-' + jenis).disabled = n === 0;
    }
    function pilihSemua(jenis, nilai) {
        document.querySelectorAll('.pilih-' + jenis).forEach(c => c.checked = nilai);
        hitung(jenis);
    }
</script>
@endsection
