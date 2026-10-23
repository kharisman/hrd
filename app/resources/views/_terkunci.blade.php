{{-- Tombol nonaktif untuk aksi eUniversity yang tidak diizinkan. $label, $ikon, $scope (string|array), $kelas (opsional) --}}
@php($butuh = implode(', ', (array) $scope))
@php($alasan = session()->has('pct_token')
    ? "Akun eUniversity Anda tidak punya izin: $butuh"
    : "Sesi eUniversity berakhir, silakan keluar lalu login ulang")
<span class="d-inline-block" tabindex="0" title="{{ $alasan }}" data-bs-toggle="tooltip">
    <button type="button" class="btn {{ $kelas ?? 'btn-outline-secondary' }}" disabled style="pointer-events:none">
        <i class="bi bi-lock me-1"></i>{{ $label }}
    </button>
</span>
