@extends('layout')
@section('title', 'Sinkron eUniversity')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pengaturan.index') }}" class="text-decoration-none">Pengaturan</a></li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Status Data</div>
            <table class="table mb-0">
                <thead><tr><th>Data</th><th class="text-end">Jumlah</th><th>Sinkron terakhir</th></tr></thead>
                @foreach ($info as [$label, $n, $waktu])
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="text-end">{{ number_format($n) }}</td>
                        <td>{{ $waktu ? $waktu->translatedFormat('d M Y H:i') : 'belum pernah' }}
                            @if ($waktu)<br><small class="{{ $waktu->lt(now()->subDays(7)) ? 'text-warning fw-semibold' : 'text-muted' }}">{{ $waktu->diffForHumans() }}</small>@endif</td>
                    </tr>
                @endforeach
            </table>
            <div class="card-body border-top">
                @include('_tombol-sinkron', ['label' => 'Sinkron Sekarang', 'kelas' => 'btn-primary'])
                <div class="small text-muted mt-2">Memakai akun eUniversity yang sedang login (butuh izin <code>staff.read</code> & <code>dosen.read</code>).</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Yang Dilakukan Sinkron</div>
            <div class="card-body small">
                <ul class="mb-3">
                    <li><strong>Pegawai</strong> (<code>/staff</code>): identitas, biodata, divisi, level ditimpa dari eUniversity.
                        Atasan & status kepegawaian (isian HRD) tidak diubah. Pegawai yang dihapus di eUniversity ikut terhapus.</li>
                    <li><strong>Dosen</strong> (<code>/dosen</code>): identitas & biodata ditimpa; status aktif mengikuti status login eUniversity.
                        Prodi, jabatan fungsional, serdos (isian HRD) tidak diubah.</li>
                    <li>Dosen yang juga pegawai ditautkan otomatis lewat nama. Tautan manual dari form dosen tidak ditimpa.</li>
                </ul>
                <div class="text-muted">
                    Jaringan ke server eUniversity lambat, jadi sinkron bisa beberapa menit. Supaya otomatis tiap malam, jadwalkan perintah
                    <code>php artisan hrd:sync</code> di Windows Task Scheduler / cron.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
