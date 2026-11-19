{{--
    Tarjeta de destino con su foto de portada (o un fondo de reemplazo si no tiene fotos).
    Uso: <x-destino-card :destino="$destino" :viajes="3" />
--}}
@props(['destino', 'viajes' => null])

<article {{ $attributes->class(['card va-card h-100 border-0 shadow-sm']) }}>
    <a href="{{ route('destinos.show', $destino) }}" class="text-decoration-none text-reset" tabindex="-1" aria-hidden="true">
        @if ($destino->portada)
            <img src="{{ $destino->portada->url() }}" alt="{{ $destino->portada->epigrafe ?? $destino->nombre }}"
                 class="card-img-top va-card-img object-fit-cover" loading="lazy">
        @else
            <div class="card-img-top va-card-img va-sin-foto d-flex align-items-center justify-content-center" role="img" aria-label="{{ $destino->nombre }}">
                <i class="bi bi-image-alt fs-1" aria-hidden="true"></i>
            </div>
        @endif
    </a>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
            <h3 class="h5 card-title mb-0">
                <a href="{{ route('destinos.show', $destino) }}" class="text-reset text-decoration-none stretched-link">{{ $destino->nombre }}</a>
            </h3>
            @if ($destino->destacado)
                <span class="badge va-badge-verde flex-shrink-0"><i class="bi bi-star-fill me-1"></i>Destacado</span>
            @endif
        </div>
        <p class="card-text text-body-secondary small mb-0">{{ Str::limit($destino->descripcion, 115) }}</p>
    </div>
    @if (! is_null($viajes))
        <div class="card-footer bg-white border-0 pt-0 small">
            @if ($viajes > 0)
                <span class="va-detalle fw-semibold"><i class="bi bi-calendar-check me-1"></i>{{ $viajes }} {{ $viajes === 1 ? 'viaje disponible' : 'viajes disponibles' }}</span>
            @else
                <span class="text-body-tertiary">Sin viajes a la venta por ahora</span>
            @endif
        </div>
    @endif
</article>
