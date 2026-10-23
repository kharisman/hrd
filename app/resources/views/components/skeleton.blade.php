{{-- Kerangka loading. Pakai: <x-skeleton /> atau <x-skeleton :baris="10" :kartu="false" /> --}}
@props(['baris' => 6, 'kartu' => true])
<div class="skeleton" aria-busy="true" aria-label="Memuat...">
    @if ($kartu)
        <div class="row g-3 mb-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="col-md-4"><div class="card"><div class="card-body">
                    <div class="sk sk-teks w-50 mb-2"></div><div class="sk sk-judul w-75"></div>
                </div></div></div>
            @endfor
        </div>
    @endif
    <div class="card">
        <div class="bar-filter"><div class="sk sk-input"></div><div class="sk sk-input"></div><div class="sk sk-input"></div></div>
        <div class="card-body">
            @for ($i = 0; $i < $baris; $i++)
                <div class="d-flex gap-3 align-items-center py-2 {{ $i ? 'border-top' : '' }}">
                    <div class="sk sk-teks" style="width:12%"></div>
                    <div class="flex-grow-1"><div class="sk sk-teks w-50 mb-1"></div><div class="sk sk-teks-kecil w-25"></div></div>
                    <div class="sk sk-teks" style="width:15%"></div>
                    <div class="sk sk-tombol"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
