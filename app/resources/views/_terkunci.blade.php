{{-- Tombol nonaktif untuk aksi eUniversity yang tidak diizinkan. $label, $ikon, $scope (string|array), $kelas (opsional) --}}
@php($butuh = implode(', ', (array) $scope))
@php($alasan = auth('pegawai')->check() && session()->has('pct_token')
    ? "Akun eUniversity Anda tidak punya izin: $butuh"
    : "Butuh login dengan akun eUniversity yang punya izin: $butuh")
<span class="d-inline-block" tabindex="0" title="{{ $alasan }}" data-bs-toggle="tooltip">
    <button type="button" class="btn {{ $kelas ?? 'btn-outline-secondary' }}" disabled style="pointer-events:none">
        <i class="bi bi-lock me-1"></i>{{ $label }}
    </button>
</span>
