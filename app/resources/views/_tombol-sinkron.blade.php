{{-- Ke halaman Sinkron eUniversity (admin) --}}
@can('admin')
    <a href="{{ route('sinkron.index') }}" class="btn {{ $kelas ?? 'btn-outline-primary' }}"><i class="bi bi-arrow-repeat me-1"></i>{{ $label ?? 'Sinkron eUniversity' }}</a>
@endcan
