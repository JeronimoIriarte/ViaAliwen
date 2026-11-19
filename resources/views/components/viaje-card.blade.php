{{--
    Tarjeta de un viaje a la venta: recorrido, horarios, precio y butacas libres.
    Uso: <x-viaje-card :viaje="$viaje" :libres="12" />
--}}
@props(['viaje', 'libres' => null])

@php $textoDuracion = $viaje->duracionTexto(); @endphp

<article {{ $attributes->class(['card va-card va-viaje-card h-100 border-0 shadow-sm']) }}>
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <p class="small text-body-secondary mb-0">{{ $viaje->origen->nombre }}</p>
                <h3 class="h5 mb-0"><i class="bi bi-arrow-down-short va-detalle" aria-hidden="true"></i>{{ $viaje->destino->nombre }}</h3>
            </div>
            <span class="fs-5 fw-bold text-nowrap" style="color: var(--va-azul)">@pesos($viaje->precio)</span>
        </div>

        <dl class="row small mb-3 g-0">
            <dt class="col-5 fw-normal text-body-secondary">Salida</dt>
            <dd class="col-7 mb-1 fw-semibold">{{ $viaje->salida->isoFormat('ddd D/MM · HH:mm') }} h</dd>
            <dt class="col-5 fw-normal text-body-secondary">Llegada estimada</dt>
            <dd class="col-7 mb-1">{{ $viaje->llegada_estimada->isoFormat('ddd D/MM · HH:mm') }} h</dd>
            <dt class="col-5 fw-normal text-body-secondary">Duración aprox.</dt>
            <dd class="col-7 mb-0">{{ $textoDuracion }}</dd>
        </dl>

        <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
            @if (! is_null($libres))
                <span class="small {{ $libres <= 5 ? 'text-danger fw-semibold' : 'text-body-secondary' }}">
                    <i class="bi bi-person-fill me-1" aria-hidden="true"></i>{{ $libres <= 5 ? "¡Quedan {$libres}!" : "{$libres} butacas libres" }}
                </span>
            @endif
            <a href="{{ route('viajes.show', $viaje) }}" class="btn btn-sm btn-primary stretched-link">Ver butacas</a>
        </div>
    </div>
</article>
