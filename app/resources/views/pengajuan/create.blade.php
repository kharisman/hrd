@extends('layout')
@section('title', $jenis ? $jenis->nama : 'Buat Pengajuan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengajuan.index') }}" class="text-decoration-none">Pengajuan</a></li>
    @if ($jenis)<li class="breadcrumb-item"><a href="{{ route('pengajuan.create') }}" class="text-decoration-none">Pilih Jenis</a></li>@endif
@endsection

@section('content')
@if (!$jenis)
    {{-- Langkah 1: pilih jenis form --}}
    @foreach ($daftar as $kategori => $items)
        <h6 class="text-muted text-uppercase small fw-semibold mt-3 mb-2">{{ $kategori }}</h6>
        <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-4 mb-2">
            @foreach ($items as $j)
                <div class="col">
                    <a href="?jenis={{ $j->id }}" class="card kartu h-100 text-decoration-none text-body">
                        <div class="card-body">
                            <div class="fw-semibold">{{ $j->nama }}</div>
                            <div class="text-muted small mb-2">{{ $j->keterangan }}</div>
                            <span class="badge text-bg-light">{{ \App\Models\JenisPengajuan::ALUR[$j->alur] }}</span>
                            @if ($j->boleh_anonim)<span class="badge text-bg-light">Bisa anonim</span>@endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endforeach
@else
    {{-- Langkah 2: isi form --}}
    <form method="post" action="{{ route('pengajuan.store') }}" enctype="multipart/form-data" class="card" style="max-width:820px"><div class="card-body">
        @csrf
        <input type="hidden" name="jenis_pengajuan_id" value="{{ $jenis->id }}">
        @if ($jenis->keterangan)<p class="text-muted">{{ $jenis->keterangan }}</p>@endif

        <div class="row g-3">
            @if ($jenis->isCuti())
                <div class="col-12"><label class="form-label">Jenis Cuti *</label>
                    <select name="jenis_cuti_id" class="form-select @error('jenis_cuti_id') is-invalid @enderror" required>
                        <option value="">-</option>
                        @foreach ($rekap as $r)
                            <option value="{{ $r->jenis->id }}" @selected(old('jenis_cuti_id') == $r->jenis->id) @disabled($r->sisa === 0)>
                                {{ $r->jenis->nama }}{{ $r->sisa !== null ? " — sisa {$r->sisa} hari" : '' }}</option>
                        @endforeach
                    </select>
                    @error('jenis_cuti_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label">Mulai *</label>
                    <input type="date" name="tgl_mulai" value="{{ old('tgl_mulai') }}" class="form-control @error('tgl_mulai') is-invalid @enderror" required></div>
                <div class="col-md-6"><label class="form-label">Selesai *</label>
                    <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}" class="form-control @error('tgl_selesai') is-invalid @enderror" required>
                    @error('tgl_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif
            @foreach ($jenis->isian as $f)
                @php($nama = "isian[{$f['key']}]")
                @php($err = "isian.{$f['key']}")
                <div class="{{ in_array($f['tipe'], ['textarea', 'file']) ? 'col-12' : 'col-md-6' }}">
                    <label class="form-label">{{ $f['label'] }}{{ $f['wajib'] ? ' *' : '' }}</label>
                    @switch($f['tipe'])
                        @case('textarea')
                            <textarea name="{{ $nama }}" rows="3" class="form-control @error($err) is-invalid @enderror" @required($f['wajib'])>{{ old($err) }}</textarea>
                            @break
                        @case('select')
                            <select name="{{ $nama }}" class="form-select @error($err) is-invalid @enderror" @required($f['wajib'])>
                                <option value="">-</option>
                                @foreach ($f['opsi'] ?? [] as $o)<option @selected(old($err) === $o)>{{ $o }}</option>@endforeach
                            </select>
                            @break
                        @case('file')
                            <input type="file" name="{{ $nama }}" class="form-control @error($err) is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" @required($f['wajib'])>
                            <div class="form-text">PDF / gambar / Office, maks. 5 MB.</div>
                            @break
                        @case('rupiah')
                            <div class="input-group"><span class="input-group-text">Rp</span>
                                <input name="{{ $nama }}" value="{{ old($err) }}" inputmode="numeric" class="form-control @error($err) is-invalid @enderror" @required($f['wajib'])
                                       oninput="this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"></div>
                            @break
                        @default
                            <input type="{{ ['date' => 'date', 'time' => 'time', 'number' => 'number'][$f['tipe']] ?? 'text' }}" name="{{ $nama }}" value="{{ old($err) }}"
                                   class="form-control @error($err) is-invalid @enderror" @required($f['wajib']) @if ($f['tipe'] === 'number') min="0" @endif>
                    @endswitch
                    @error($err)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>

        @if ($jenis->boleh_anonim)
            <div class="form-check mt-3">
                <input type="checkbox" name="anonim" value="1" id="anonim" class="form-check-input" @checked(old('anonim'))>
                <label for="anonim" class="form-check-label">Kirim sebagai <strong>anonim</strong> (nama Anda tidak ditampilkan ke atasan maupun HRD)</label>
            </div>
        @endif

        <div class="alert alert-light border small mt-3 mb-0">
            <i class="bi bi-diagram-3 me-1"></i> Alur: <strong>{{ \App\Models\JenisPengajuan::ALUR[$jenis->alur] }}</strong>.
            @if ($jenis->alur !== 'hrd')
                Atasan Anda: <strong>{{ $staff->atasan->nama ?? 'belum diatur' }}</strong>{{ $staff->atasan ? '' : ' (pengajuan langsung ke HRD)' }}.
            @endif
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Pengajuan</button>
            <a href="{{ route('pengajuan.create') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div></form>

    @if ($jenis->isCuti())
        <div class="card mt-3" style="max-width:820px">
            <div class="card-header">Jatah Cuti {{ now()->year }}</div>
            <table class="table mb-0">
                <thead><tr><th>Jenis</th><th class="text-end">Jatah</th><th class="text-end">Terpakai</th><th class="text-end">Sisa</th></tr></thead>
                @foreach ($rekap as $r)
                    <tr>
                        <td>{{ $r->jenis->nama }}@if ($r->jenis->keterangan)<br><small class="text-muted">{{ $r->jenis->keterangan }}</small>@endif</td>
                        <td class="text-end">{{ $r->kuota ?? '—' }}</td>
                        <td class="text-end">{{ $r->terpakai }}</td>
                        <td class="text-end fw-semibold {{ $r->sisa === 0 ? 'text-danger' : '' }}">{{ $r->sisa ?? '∞' }}</td>
                    </tr>
                @endforeach
            </table>
            <div class="card-footer small text-muted">Jatah berlaku per tahun kalender dan tidak diakumulasi. Terpakai = disetujui + masih menunggu.</div>
        </div>
    @endif
@endif
@endsection
