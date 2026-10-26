@extends('layout')
@section('title', $staff->exists ? 'Ubah Pegawai' : 'Tambah Pegawai')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('staff.index') }}" class="text-decoration-none">Data Pegawai</a></li>
    @if ($staff->exists)<li class="breadcrumb-item"><a href="{{ route('staff.show', $staff) }}" class="text-decoration-none">{{ $staff->nama }}</a></li>@endif
@if (!empty($strukturMap) && count($strukturMap))
<script>
    // Level yang ditawarkan mengikuti struktur eUniversity untuk divisi terpilih
    (() => {
        const peta = @json($strukturMap), div = document.querySelector('[name=id_divisi]'), lev = document.querySelector('[name=id_level]');
        const saring = () => [...lev.options].forEach(o => o.hidden = o.value && div.value && !(peta[div.value] || []).map(String).includes(o.value));
        div.addEventListener('change', () => { saring(); if (lev.selectedOptions[0]?.hidden) lev.value = ''; });
        saring();
    })();
</script>
@endif
@endsection

@section('content')
<form method="post" action="{{ $staff->exists ? route('staff.update', $staff) : route('staff.store') }}" class="card"><div class="card-body">
    @csrf
    @if ($staff->exists) @method('put') @endif

    @include('_biodata', ['m' => $staff])

    <h6 class="mt-4 mb-3 border-bottom pb-2">Kepegawaian</h6>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">NIP *</label><input name="nip" value="{{ old('nip', $staff->nip) }}" class="form-control @error('nip') is-invalid @enderror" required>@error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Divisi *</label>
            <select name="id_divisi" class="form-select" required><option value="">-</option>
                @foreach ($divisiList as $id => $nama)<option value="{{ $id }}" @selected(old('id_divisi', $staff->id_divisi) == $id)>{{ $nama }}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label">Level / Jabatan *</label>
            <select name="id_level" class="form-select @error('id_level') is-invalid @enderror" required><option value="">-</option>
                @foreach ($levelList as $id => $nama)<option value="{{ $id }}" @selected(old('id_level', $staff->id_level) == $id)>{{ $nama }}</option>@endforeach
            </select></div>
        <div class="col-12 form-text mt-1">Divisi + level menentukan role login eUniversity (mis. BAAK, Kaprodi).</div>
    </div>

    <h6 class="mt-4 mb-3 border-bottom pb-2">Data HRD <small class="text-muted fw-normal">(hanya di HRD, tidak dikirim ke eUniversity)</small></h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Atasan Langsung</label>
            <select name="atasan_id" class="form-select @error('atasan_id') is-invalid @enderror"><option value="">— Belum diatur —</option>
                @foreach ($atasanList as $a)<option value="{{ $a->id }}" @selected(old('atasan_id', $staff->atasan_id) == $a->id)>{{ $a->nama }} — {{ $a->level }}, {{ $a->divisi }}</option>@endforeach
            </select>
            @error('atasan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Atasan yang menyetujui cuti &amp; pengajuan pegawai ini.</div></div>
        <div class="col-md-3"><label class="form-label">Status Kepegawaian</label>
            <select name="status_kepegawaian" class="form-select"><option value="">-</option>
                @foreach (\App\Models\Staff::STATUS as $o)<option @selected(old('status_kepegawaian', $staff->status_kepegawaian) == $o)>{{ $o }}</option>@endforeach
            </select></div>
        <div class="col-md-3"><label class="form-label">Pendidikan Terakhir</label>
            <input class="form-control" value="{{ $staff->pendidikan_terakhir ?? '-' }}" disabled>
            <div class="form-text">Otomatis dari riwayat pendidikan.</div></div>
    </div>

    <div class="mt-4 d-flex gap-2">
        @php($scopeSimpan = $staff->exists ? 'staff.update' : 'staff.create')
        @can('api', $scopeSimpan)
            <button class="btn btn-primary">Simpan</button>
        @else
            @include('_terkunci', ['label' => 'Simpan', 'scope' => $scopeSimpan, 'kelas' => 'btn-secondary'])
        @endcan
        <a href="{{ $staff->exists ? route('staff.show', $staff) : route('staff.index') }}" class="btn btn-outline-secondary">Batal</a>
        @if ($staff->exists)
            <span class="ms-auto">
                @can('api', 'staff.delete')
                    <button form="hapus" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                @else
                    @include('_terkunci', ['label' => 'Hapus', 'scope' => 'staff.delete'])
                @endcan
            </span>
        @endif
    </div>
</div></form>
@if ($staff->exists)
<form id="hapus" method="post" action="{{ route('staff.destroy', $staff) }}" onsubmit="return confirm('Hapus {{ e($staff->nama) }} dari eUniversity dan HRD?')">@csrf @method('delete')</form>
@endif
@endsection
