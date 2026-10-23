@extends('layout')
@section('title', $judul)

@section('content')
<div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-4">
    @foreach ($item as [$route, $ikon, $label, $ket])
        <div class="col">
            <a href="{{ route($route) }}" class="card kartu h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="ikon mb-3"><i class="bi bi-{{ $ikon }}"></i></div>
                    <div class="fw-semibold mb-1">{{ $label }}</div>
                    <div class="text-muted small">{{ $ket }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
