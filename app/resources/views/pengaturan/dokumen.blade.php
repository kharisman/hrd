@extends('layout')
@section('title', 'Dokumen & Penandatangan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <form method="post" action="{{ route('pengaturan.dokumen.simpan') }}" class="card"><div class="card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Pejabat Penandatangan</label>
                <select name="ttd_staff_id" class="form-select">
                    <option value="">Otomatis — pegawai aktif berjabatan Rektor</option>
                    @foreach ($pilihan as $s)<option value="{{ $s->id }}" @selected($terpilih == $s->id)>{{ $s->nama }} — {{ $s->level }}{{ $s->nip ? ' (' . $s->nip . ')' : '' }}</option>@endforeach
                </select>
                <div class="form-text">Pilih pejabat lain (mis. Wakil Rektor sebagai Plt) bila Rektor berhalangan. Daftar berasal dari data pegawai aktif eUniversity.</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-5"><label class="form-label">Kota (tempat tanda tangan)</label><input name="kota" value="{{ old('kota', $kota) }}" class="form-control" required></div>
                <div class="col-md-7"><label class="form-label">Kode Nomor Surat</label><input name="kode_surat" value="{{ old('kode_surat', $kodeSurat) }}" class="form-control @error('kode_surat') is-invalid @enderror" required>
                    <div class="form-text">Format nomor: <code>001/{{ $kodeSurat }}/{{ ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1] }}/{{ now()->year }}</code> (urut per tahun)</div></div>
            </div>
            <button class="btn btn-primary">Simpan</button>
        </div></form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Penandatangan Saat Ini</div>
            <div class="card-body">
                @if ($aktif)
                    <div class="fw-semibold">{{ $aktif->nama }}</div>
                    <div class="text-muted small">{{ $aktif->level }} ITB PalComTech{{ $aktif->nip ? ' · NIP ' . $aktif->nip : '' }}</div>
                @else
                    <div class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Belum ada pegawai aktif berjabatan Rektor. Pilih pejabat penandatangan di samping.</div>
                @endif
                <hr>
                <div class="small text-muted">
                    Saat pengajuan <strong>disetujui</strong>, PDF-nya mendapat nomor surat dan tanda tangan pejabat ini berupa <strong>QR code</strong>.
                    QR mengarah ke halaman validasi publik yang menunjukkan dokumen SAH. Nama pejabat <strong>disimpan per dokumen</strong>,
                    jadi dokumen lama tetap atas nama pejabat saat itu walau Rektor berganti.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
