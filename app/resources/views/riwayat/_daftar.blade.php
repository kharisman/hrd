{{-- Kartu riwayat pendidikan. $riwayat, $staffId / $dosenId = pemilik (untuk tombol Tambah) --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        Riwayat Pendidikan & Studi Lanjut
        @can('admin')
            <a href="{{ route('riwayat.create', array_filter(['staff_id' => $staffId ?? null, 'dosen_id' => $dosenId ?? null])) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
        @endcan
    </div>
    @forelse ($riwayat as $r)
        <div class="card-body border-bottom py-2 d-flex justify-content-between gap-2">
            <div>
                <span class="badge text-bg-secondary">{{ $r->jenjang }}</span>
                <strong>{{ $r->institusi }}</strong>
                @if ($r->program_studi)<span class="text-muted">— {{ $r->program_studi }}</span>@endif
                <div class="small text-muted">
                    {{ $r->tahun_masuk ?? '?' }}–{{ $r->tahun_lulus ?? '?' }}{{ $r->status === 'Sedang Studi Lanjut' ? ' (target)' : '' }}
                    · <span class="badge text-bg-{{ ['Lulus' => 'success', 'Sedang Studi Lanjut' => 'info', 'Tidak Selesai' => 'danger'][$r->status] }}">{{ $r->status }}</span>
                    @if ($r->pembiayaan) · {{ $r->pembiayaan }}@endif
                    @if ($r->keterangan)<br>{{ $r->keterangan }}@endif
                </div>
            </div>
            <div class="text-nowrap">
                @if ($r->berkas)<a href="{{ route('riwayat.berkas', $r) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat berkas"><i class="bi bi-file-earmark-text"></i></a>@endif
                @can('admin')<a href="{{ route('riwayat.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>@endcan
            </div>
        </div>
    @empty
        <div class="card-body text-muted small">Belum ada riwayat pendidikan.</div>
    @endforelse
</div>
