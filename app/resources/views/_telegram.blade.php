{{-- Kartu chat ID Telegram (login OTP). $model = Staff/Dosen, $aksi = URL simpan --}}
<div class="card">
    <div class="card-header"><i class="bi bi-telegram text-primary me-1"></i>Telegram (Login OTP)</div>
    <div class="card-body">
        @can('admin')
            <form method="post" action="{{ $aksi }}">
                @csrf
                <div class="input-group">
                    <input name="telegram" value="{{ old('telegram', $model->telegram) }}" class="form-control @error('telegram') is-invalid @enderror" placeholder="Chat ID (angka)" inputmode="numeric">
                    <button class="btn btn-outline-primary">Simpan</button>
                </div>
                @error('telegram')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
            <div class="form-text">Dipakai untuk mengirim kode OTP saat login dengan NIP/NIDN. Pegawai harus membuka bot dan menekan <em>Start</em>.</div>
        @else
            {!! $model->telegram ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Terdaftar</span>' : '<span class="text-muted">Belum terdaftar. Hubungi bagian SDM.</span>' !!}
        @endcan
    </div>
</div>
