{{-- Tombol sinkron eUniversity: butuh scope staff.read & dosen.read milik akun yang login --}}
@can('api', ['staff.read', 'dosen.read'])
    <form method="post" action="{{ route('dosen.sinkron') }}">
        @csrf
        <button class="btn {{ $kelas ?? 'btn-outline-primary' }}"><i class="bi bi-arrow-repeat me-1"></i>{{ $label ?? 'Sinkron eUniversity' }}</button>
    </form>
@else
    @include('_terkunci', ['label' => $label ?? 'Sinkron eUniversity', 'scope' => ['staff.read', 'dosen.read'], 'kelas' => $kelas ?? 'btn-outline-secondary'])
@endcan
