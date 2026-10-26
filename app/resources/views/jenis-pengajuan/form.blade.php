@extends('layout')
@section('title', $j->exists ? 'Ubah Jenis Pengajuan' : 'Tambah Jenis Pengajuan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
    <li class="breadcrumb-item"><a href="{{ route('jenis-pengajuan.index') }}" class="text-decoration-none">Jenis Pengajuan</a></li>
@endsection

@php($isian = old('isian', $j->isian))
@php($tahapan = old('tahapan', $j->tahapan ?? ['atasan', 'hrd']))

@section('content')
<form method="post" action="{{ $j->exists ? route('jenis-pengajuan.update', $j) : route('jenis-pengajuan.store') }}" class="card"><div class="card-body">
    @csrf
    @if ($j->exists) @method('put') @endif

    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Nama Form *</label><input name="nama" value="{{ old('nama', $j->nama) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Kategori *</label>
            <input name="kategori" value="{{ old('kategori', $j->kategori) }}" list="kategoriList" class="form-control" required>
            <datalist id="kategoriList">@foreach (\App\Models\JenisPengajuan::KATEGORI as $k)<option>{{ $k }}</option>@endforeach</datalist></div>
        <div class="col-md-9"><label class="form-label">Keterangan</label><input name="keterangan" value="{{ old('keterangan', $j->keterangan) }}" class="form-control" placeholder="Tampil di bawah judul form"></div>
        <div class="col-md-3 d-flex align-items-end gap-3">
            <div class="form-check"><input type="checkbox" name="aktif" value="1" id="aktif" class="form-check-input" @checked(old('aktif', $j->aktif))><label for="aktif" class="form-check-label">Aktif</label></div>
            <div class="form-check"><input type="checkbox" name="boleh_anonim" value="1" id="anonim" class="form-check-input" @checked(old('boleh_anonim', $j->boleh_anonim))><label for="anonim" class="form-check-label">Boleh anonim</label></div>
        </div>
    </div>

    <h6 class="mt-4 mb-1">Alur Persetujuan *</h6>
    <div class="form-text mt-0 mb-2">Urutan tahap dari kiri ke kanan. Setelah tahap terakhir menyetujui, pengajuan sah (nomor surat + QR Rektor).
        Tahap <strong>Atasan</strong> dilewati bila pegawai belum punya atasan atau mengirim anonim.
        Pemegang Warek / Keuangan / BAAK diatur di <a href="{{ route('pengaturan.pejabat') }}">Pengaturan → Peran Pejabat</a>.</div>
    <div id="alur" class="d-flex flex-wrap align-items-center gap-2 mb-1">
        @foreach ($tahapan as $t)
            <div class="tahap input-group input-group-sm" style="width:auto">
                <span class="input-group-text nomor"></span>
                <select name="tahapan[]" class="form-select form-select-sm" style="width:auto">@foreach (\App\Models\JenisPengajuan::TAHAP as $v => $l)<option value="{{ $v }}" @selected($t === $v)>{{ $l }}</option>@endforeach</select>
                <button type="button" class="btn btn-outline-secondary" onclick="geserTahap(this)" title="Pindah ke kiri"><i class="bi bi-arrow-left"></i></button>
                <button type="button" class="btn btn-outline-danger" onclick="hapusTahap(this)" title="Hapus tahap"><i class="bi bi-x"></i></button>
            </div>
        @endforeach
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahTahap()"><i class="bi bi-plus-lg me-1"></i>Tahap</button>
    </div>
    @error('tahapan')<div class="text-danger small">{{ $message }}</div>@enderror
    @if ($j->exists && $j->pengajuan()->exists())
        <div class="form-text"><i class="bi bi-info-circle me-1"></i>Mengubah alur hanya berlaku untuk pengajuan baru; yang sedang berjalan tetap memakai alur saat diajukan.</div>
    @endif

    <h6 class="mt-4 mb-2">Isian Form</h6>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-2">
        <thead class="table-light"><tr><th>Label *</th><th style="width:170px">Tipe</th><th style="width:70px" class="text-center">Wajib</th><th>Opsi <small class="text-muted fw-normal">(untuk Pilihan, pisahkan koma)</small></th><th style="width:40px"></th></tr></thead>
        <tbody id="isian">
            @foreach ($isian as $i => $f)
                <tr>
                    <td><input type="hidden" name="isian[{{ $i }}][key]" value="{{ $f['key'] ?? '' }}">
                        <input name="isian[{{ $i }}][label]" value="{{ $f['label'] }}" class="form-control form-control-sm" required></td>
                    <td><select name="isian[{{ $i }}][tipe]" class="form-select form-select-sm">
                        @foreach (\App\Models\JenisPengajuan::TIPE as $v => $t)<option value="{{ $v }}" @selected(($f['tipe'] ?? '') === $v)>{{ $t }}</option>@endforeach</select></td>
                    <td class="text-center"><input type="checkbox" name="isian[{{ $i }}][wajib]" value="1" class="form-check-input" @checked(!empty($f['wajib']))></td>
                    <td><input name="isian[{{ $i }}][opsi]" value="{{ is_array($f['opsi'] ?? null) ? implode(', ', $f['opsi']) : ($f['opsi'] ?? '') }}" class="form-control form-control-sm"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()" title="Hapus"><i class="bi bi-x"></i></button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahIsian()"><i class="bi bi-plus-lg me-1"></i>Tambah Isian</button>
    @if ($j->exists && $j->pengajuan()->exists())
        <div class="form-text"><i class="bi bi-info-circle me-1"></i>Jenis ini sudah dipakai. Mengganti label aman; menghapus isian membuat jawaban lama untuk isian itu tidak ditampilkan lagi.</div>
    @endif

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('jenis-pengajuan.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div></form>

<template id="barisIsian">
    <tr>
        <td><input type="hidden" name="isian[__i__][key]" value=""><input name="isian[__i__][label]" class="form-control form-control-sm" required></td>
        <td><select name="isian[__i__][tipe]" class="form-select form-select-sm">@foreach (\App\Models\JenisPengajuan::TIPE as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select></td>
        <td class="text-center"><input type="checkbox" name="isian[__i__][wajib]" value="1" class="form-check-input" checked></td>
        <td><input name="isian[__i__][opsi]" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()" title="Hapus"><i class="bi bi-x"></i></button></td>
    </tr>
</template>
<template id="barisTahap">
    <div class="tahap input-group input-group-sm" style="width:auto">
        <span class="input-group-text nomor"></span>
        <select name="tahapan[]" class="form-select form-select-sm" style="width:auto">@foreach (\App\Models\JenisPengajuan::TAHAP as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        <button type="button" class="btn btn-outline-secondary" onclick="geserTahap(this)" title="Pindah ke kiri"><i class="bi bi-arrow-left"></i></button>
        <button type="button" class="btn btn-outline-danger" onclick="hapusTahap(this)" title="Hapus tahap"><i class="bi bi-x"></i></button>
    </div>
</template>
<script>
    // Editor alur: nomor tahap dihitung ulang setiap ada perubahan
    function nomoriTahap() { document.querySelectorAll('#alur .tahap .nomor').forEach((n, i) => n.textContent = i + 1); }
    function tambahTahap() {
        const tombol = document.querySelector('#alur > button');
        tombol.insertAdjacentHTML('beforebegin', document.getElementById('barisTahap').innerHTML);
        nomoriTahap();
    }
    function geserTahap(b) { const t = b.closest('.tahap'), kiri = t.previousElementSibling; if (kiri) { t.after(kiri); nomoriTahap(); } }
    function hapusTahap(b) { if (document.querySelectorAll('#alur .tahap').length > 1) { b.closest('.tahap').remove(); nomoriTahap(); } }
    nomoriTahap();

    let urut = {{ count($isian) + 100 }};
    function tambahIsian() {
        const html = document.getElementById('barisIsian').innerHTML.replaceAll('__i__', urut++);
        document.getElementById('isian').insertAdjacentHTML('beforeend', html);
    }
</script>
@endsection
