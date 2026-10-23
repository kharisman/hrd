{{-- Biodata wajib eUniversity. Nama input = nama kolom API. $m = model Dosen / Staff --}}
@php
    $tgl = fn ($f) => old($f, $m->$f?->format('Y-m-d'));
    $jk = old('jenis_kelamin', ['L' => 'Pria', 'P' => 'Wanita'][$m->jenis_kelamin] ?? null);
@endphp
<h6 class="mb-3 border-bottom pb-2">Akun & Biodata <small class="text-muted fw-normal">(tersimpan juga di eUniversity)</small></h6>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nama Lengkap & Gelar *</label><input name="nama" value="{{ old('nama', $m->nama) }}" class="form-control @error('nama') is-invalid @enderror" required>@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label">Username eUniversity *</label><input name="username" value="{{ old('username', $m->username) }}" class="form-control @error('username') is-invalid @enderror" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" placeholder="{{ $m->exists ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter' }}">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label">Jenis Kelamin *</label>
        <select name="jenis_kelamin" class="form-select" required><option value="">-</option>@foreach (['Pria', 'Wanita'] as $o)<option @selected($jk == $o)>{{ $o }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Tempat Lahir *</label><input name="tempat_lahir" value="{{ old('tempat_lahir', $m->tempat_lahir) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Tanggal Lahir *</label><input type="date" name="tgl_lahir" value="{{ $tgl('tgl_lahir') }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Mulai Kerja *</label><input type="date" name="mulai_kerja" value="{{ $tgl('mulai_kerja') }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Agama *</label>
        <select name="agama" class="form-select" required><option value="">-</option>
            @foreach (collect(\App\Models\Dosen::AGAMA)->push($m->agama)->filter()->unique() as $o)<option @selected(old('agama', $m->agama) == $o)>{{ $o }}</option>@endforeach
        </select></div>
    <div class="col-md-3"><label class="form-label">Golongan Darah</label>
        <select name="gol_dar" class="form-select"><option value="">-</option>@foreach (\App\Models\Dosen::GOL_DAR as $o)<option @selected(old('gol_dar', $m->gol_dar) == $o)>{{ $o }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">No. HP *</label><input name="telp" maxlength="15" value="{{ old('telp', $m->no_hp) }}" class="form-control @error('telp') is-invalid @enderror" required>@error('telp')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-12"><label class="form-label">Alamat *</label><textarea name="alamat" rows="2" class="form-control" required>{{ old('alamat', $m->alamat) }}</textarea></div>
</div>
