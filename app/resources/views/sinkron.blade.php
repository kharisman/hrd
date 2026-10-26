@extends('layout')
@section('title', 'Sinkron eUniversity')

@section('actions')
    <button type="button" class="btn btn-primary" id="tombolSemua" data-no-ajax onclick="sinkronSemua(this)">
        <i class="bi bi-arrow-repeat me-1"></i>Sinkron Semua</button>
@endsection

@section('content')
<div class="alert alert-light border small">
    <i class="bi bi-info-circle me-1"></i>
    Data di bawah adalah <strong>salinan dari eUniversity</strong> dan tidak diubah manual di HRD. Sinkronkan per data supaya ringan;
    ikuti urutan bila data bergantung pada data lain. Semua langkah juga berjalan otomatis lewat <code>php artisan hrd:sync</code> (terjadwal tiap malam).
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Data</th><th>Isi di HRD</th><th>Sinkron terakhir</th><th>Status</th><th class="text-end"></th></tr></thead>
        @foreach ($langkah as $l)
            @php($waktu = $l->terakhir ? \Illuminate\Support\Carbon::parse($l->terakhir['waktu']) : null)
            <tr data-langkah="{{ $l->kunci }}" data-url="{{ route('sinkron.jalankan', $l->kunci) }}">
                <td>
                    <div class="fw-semibold">{{ $l->label }}</div>
                    <small class="text-muted"><code>{{ $l->endpoint }}</code>@if ($l->butuh->isNotEmpty()) · perlu lebih dulu: {{ $l->butuh->join(', ') }}@endif</small>
                </td>
                <td class="small jumlah">{{ $l->jumlah }}</td>
                <td class="small text-nowrap terakhir">
                    @if ($waktu)
                        {{ $waktu->translatedFormat('d M Y H:i') }}
                        <br><span class="{{ $waktu->lt(now()->subDays(2)) ? 'text-warning fw-semibold' : 'text-muted' }}">{{ $waktu->diffForHumans() }} · {{ number_format($l->terakhir['hasil']) }} data</span>
                    @else
                        <span class="text-muted">belum pernah</span>
                    @endif
                </td>
                <td class="small status text-nowrap"></td>
                <td class="text-end">
                    <form method="post" action="{{ route('sinkron.jalankan', $l->kunci) }}">
                        @csrf <button class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-arrow-repeat me-1"></i>Sinkron</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
    </div>
</div>
<script>
    // Sinkron Semua: satu langkah per permintaan, berurutan (urutan baris = urutan ketergantungan). Gagal → lanjut, ditandai merah.
    window.sinkronSemua = async function (tombol) {
        const baris = [...document.querySelectorAll('tr[data-langkah]')];
        const token = document.querySelector('input[name=_token]').value;
        const status = (tr, html) => tr.querySelector('.status').innerHTML = html;
        document.querySelectorAll('tr[data-langkah] button, #tombolSemua').forEach(b => b.disabled = true);
        baris.forEach(tr => status(tr, '<span class="text-muted">menunggu</span>'));
        let gagal = 0;
        for (const [i, tr] of baris.entries()) {
            tombol.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${i + 1} / ${baris.length}`;
            status(tr, '<span class="spinner-border spinner-border-sm text-primary"></span> berjalan…');
            tr.classList.add('table-active');
            try {
                const r = await fetch(tr.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json().catch(() => ({ pesan: 'Respons server tidak terbaca' }));
                if (!r.ok) throw new Error(d.pesan || d.message || ('HTTP ' + r.status));
                tr.querySelector('.jumlah').textContent = d.jumlah;
                tr.querySelector('.terakhir').innerHTML = `${d.waktu}<br><span class="text-muted">baru saja · ${d.hasil} data</span>`;
                status(tr, '<span class="text-success"><i class="bi bi-check-circle-fill"></i> selesai</span>');
            } catch (e) {
                gagal++;
                status(tr, `<span class="text-danger" title="${e.message}"><i class="bi bi-x-circle-fill"></i> gagal</span>`);
            }
            tr.classList.remove('table-active');
        }
        document.querySelectorAll('tr[data-langkah] button, #tombolSemua').forEach(b => b.disabled = false);
        tombol.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sinkron Semua';
        HRD.toast(gagal ? `Selesai, ${gagal} data gagal disinkron.` : 'Semua data selesai disinkron.', gagal ? 'danger' : 'success');
    };
</script>
@endsection
