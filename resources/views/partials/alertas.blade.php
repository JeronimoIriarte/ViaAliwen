{{-- Mensajes de confirmación y error que los controladores dejan en la sesión con ->with(). --}}
@php
    $alertas = [
        'exito' => ['success', 'bi-check-circle-fill'],
        'info' => ['info', 'bi-info-circle-fill'],
        'error' => ['danger', 'bi-exclamation-triangle-fill'],
    ];
@endphp

@foreach ($alertas as $clave => [$color, $icono])
    @if (session($clave))
        <div class="container mt-3">
            <div class="alert alert-{{ $color }} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi {{ $icono }}" aria-hidden="true"></i>
                <div>{{ session($clave) }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    @endif
@endforeach
