{{-- Navigasi bagian Dashboard Dosen --}}
<ul class="nav nav-tabs mb-3">
    @foreach (['dashboard.dosen' => ['clipboard-data', 'Kualifikasi'], 'dashboard.dosen.mk' => ['grid-3x3', 'Penguasaan MK'], 'dashboard.dosen.kompetensi' => ['stars', 'Kompetensi'], 'dashboard.dosen.rapor.index' => ['person-vcard', 'Rapor Dosen']] as $r => [$i, $l])
        <li class="nav-item"><a class="nav-link {{ request()->routeIs($r) || ($r === 'dashboard.dosen.rapor.index' && request()->routeIs('dashboard.dosen.rapor')) ? 'active' : '' }}" href="{{ route($r, array_filter(['prodi' => request('prodi')])) }}"><i class="bi bi-{{ $i }} me-1"></i>{{ $l }}</a></li>
    @endforeach
</ul>
