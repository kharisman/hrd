{{-- Grafik pie (meniru pie di Excel Dashboard Dosen): $data = [label => jumlah], $judul, $catatan opsional.
     CSS conic-gradient, tanpa library; legenda memuat jumlah & persen. --}}
@php($warna = ['#4285f4', '#ea4335', '#fbbc04', '#34a853', '#ff6d01', '#46bdc6'])
@php($total = $data->sum())
@php($sudut = 0)
@php($irisan = $data->values()->map(function ($n, $i) use ($total, $warna, &$sudut) {
    $awal = $sudut; $sudut += $total ? $n / $total * 360 : 0;
    return $warna[$i % count($warna)] . " {$awal}deg {$sudut}deg";
})->join(', '))
<div class="card h-100">
    <div class="card-header">{{ $judul }}</div>
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        @if ($total)
            <div class="flex-shrink-0 rounded-circle" style="width:130px;height:130px;background:conic-gradient({{ $irisan }})" role="img"
                 aria-label="{{ $data->map(fn ($n, $l) => "$l $n")->join(', ') }}"></div>
            <ul class="list-unstyled small mb-0 flex-grow-1">
                @foreach ($data as $label => $n)
                    <li class="d-flex align-items-center gap-2 py-1">
                        <span class="flex-shrink-0 rounded-1" style="width:12px;height:12px;background:{{ $warna[$loop->index % count($warna)] }}"></span>
                        <span class="flex-grow-1">{{ $label }}</span>
                        <span class="text-muted text-nowrap">{{ $n }} · {{ round($n / $total * 100) }}%</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-muted small">Belum ada data.</div>
        @endif
        @isset($catatan)<div class="small text-muted w-100">{{ $catatan }}</div>@endisset
    </div>
</div>
