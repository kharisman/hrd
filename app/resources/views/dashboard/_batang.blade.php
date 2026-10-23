{{-- Grafik batang horizontal satu seri: $data = [label => jumlah] (sudah urut), $judul.
     Satu warna (besaran, bukan identitas), angka di samping batang, title = keterangan saat disorot. --}}
@php($maks = max(1, $data->max() ?? 1))
@php($total = max(1, $data->sum()))
<div class="card h-100">
    <div class="card-header">{{ $judul }}</div>
    <div class="card-body">
        @forelse ($data as $label => $n)
            <div class="batang" title="{{ $label }}: {{ $n }} ({{ round($n / $total * 100) }}%)">
                <div class="batang-label text-truncate">{{ $label }}</div>
                <div class="batang-jalur"><div class="batang-isi {{ str_starts_with($label, 'Belum') || str_starts_with($label, 'Tanpa') ? 'kosong' : '' }}" style="width: {{ $n / $maks * 100 }}%"></div></div>
                <div class="batang-nilai">{{ $n }}</div>
            </div>
        @empty
            <div class="text-muted small">Belum ada data.</div>
        @endforelse
    </div>
</div>
