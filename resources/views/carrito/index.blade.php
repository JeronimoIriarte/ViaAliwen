@extends('layouts.app')

@section('titulo', 'Carrito')

@section('contenido')
<div class="container py-5">
    <h1 class="h3 mb-1"><i class="bi bi-cart3 me-2 va-detalle"></i>Tu carrito</h1>
    <p class="text-body-secondary mb-4">Podés comprar varios viajes en una misma operación: una butaca por viaje.</p>

    @if ($detalle->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-cart-x display-4 text-body-tertiary" aria-hidden="true"></i>
                <p class="lead mt-3">Tu carrito está vacío.</p>
                <a href="{{ route('viajes.index') }}" class="btn btn-primary">Buscar viajes</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($detalle as $item)
                    <article class="card border-0 shadow-sm mb-3 @if ($item['error']) border-start border-4 border-danger @endif">
                        <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                @if ($item['viaje'])
                                    <h2 class="h5 mb-1">{{ $item['viaje']->origen->nombre }} <i class="bi bi-arrow-right"></i> {{ $item['viaje']->destino->nombre }}</h2>
                                    <p class="mb-1 small">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $item['viaje']->salida->isoFormat('dddd D [de] MMMM, HH:mm') }} h
                                        · llegada aprox. {{ $item['viaje']->llegada_estimada->format('d/m H:i') }} h
                                    </p>
                                    <p class="mb-0 small text-body-secondary">
                                        <i class="bi bi-bus-front me-1"></i>Colectivo N.º {{ $item['colectivo']?->numero_interno ?? '—' }}
                                        · <i class="bi bi-person-fill me-1"></i>Butaca {{ $item['nro_butaca'] }}
                                    </p>
                                @else
                                    <h2 class="h5 mb-1">Viaje no disponible</h2>
                                @endif
                                @if ($item['error'])
                                    <p class="text-danger small fw-semibold mt-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>{{ $item['error'] }} Quitalo para continuar.</p>
                                @endif
                            </div>
                            <div class="text-end">
                                <p class="fs-5 fw-bold mb-2">@pesos($item['precio'])</p>
                                <form method="POST" action="{{ route('carrito.destroy', $item['clave']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Quitar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
                <a href="{{ route('viajes.index') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Seguir comprando</a>
            </div>

            <div class="col-lg-4">
                <aside class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Resumen</h2>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $detalle->whereNull('error')->count() }} {{ $detalle->whereNull('error')->count() === 1 ? 'pasaje' : 'pasajes' }}</span>
                            <span>@pesos($total)</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
                            <span>Total</span>
                            <span style="color: var(--va-azul)">@pesos($total)</span>
                        </div>

                        @if (! $pagosConfigurados)
                            <div class="alert alert-warning small">Los pagos todavía no están configurados (falta la credencial de Mercado Pago).</div>
                        @endif

                        <form method="POST" action="{{ route('checkout.store') }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 py-2" @disabled($hayErrores || ! $pagosConfigurados)>
                                <i class="bi bi-credit-card me-1"></i>Reservar y pagar con Mercado Pago
                            </button>
                        </form>
                        <p class="small text-body-secondary mt-3 mb-0">
                            Al continuar, tus butacas quedan reservadas por {{ $minutosReserva }} minutos y el pago se abre
                            en una pestaña nueva. Los pasajes se emiten cuando Mercado Pago aprueba el pago.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    @endif
</div>
@endsection
