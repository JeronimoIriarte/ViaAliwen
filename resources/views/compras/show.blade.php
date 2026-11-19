@extends('layouts.app')

@section('titulo', 'Detalle de la compra')

@section('contenido')
@use('App\Enums\EstadoCompra')
@php $pendiente = $compra->estado === EstadoCompra::Pendiente; @endphp

<div class="container py-5">
    <a href="{{ route('compras.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a mis compras</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Compra <code class="fs-5">{{ Str::limit($compra->referencia, 8, '') }}</code></h1>
            <p class="text-body-secondary mb-0">
                {{ $compra->created_at->isoFormat('dddd D [de] MMMM YYYY, HH:mm') }}
                @if ($compra->pagada_en) · pagada el {{ $compra->pagada_en->format('d/m/Y H:i') }} @endif
            </p>
        </div>
        <div class="text-end">
            <span class="badge fs-6 text-bg-{{ $compra->estado->color() }}">{{ $compra->estado->etiqueta() }}</span>
            <p class="fs-4 fw-bold mb-0 mt-1" style="color: var(--va-azul)">@pesos($compra->total)</p>
        </div>
    </div>

    @if ($pendiente)
        {{-- data-estado-url: compra-pendiente.js consulta el estado y recarga la página cuando el pago se aprueba --}}
        <div class="card border-warning mb-4" data-estado-url="{{ route('compras.estado', $compra) }}"
             data-expira="{{ $compra->expira_en->toIso8601String() }}">
            <div class="card-body">
                <h2 class="h6"><i class="bi bi-hourglass-split me-1"></i>Pago pendiente</h2>
                <p class="small mb-3">
                    Tus butacas están reservadas hasta las <strong>{{ $compra->expira_en->format('H:i') }}</strong>
                    ({{ $compra->expira_en->diffForHumans() }}). El pago se abre en una <strong>pestaña nueva</strong>:
                    cuando termines, volvé a esta pestaña.
                </p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if ($compra->mp_checkout_url)
                        <a href="{{ $compra->mp_checkout_url }}" target="_blank" rel="noopener" class="btn btn-success btn-lg">
                            <i class="bi bi-credit-card me-1"></i>Pagar con Mercado Pago <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('compras.verificar', $compra) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Verificar pago</button>
                    </form>
                    <form method="POST" action="{{ route('compras.cancelar', $compra) }}" onsubmit="return confirm('¿Cancelar la compra y liberar las butacas?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Cancelar compra</button>
                    </form>
                </div>
                <p class="small text-body-secondary mt-3 mb-0 d-flex align-items-center gap-2" data-esperando aria-live="polite">
                    <span class="spinner-border spinner-border-sm text-success" aria-hidden="true"></span>
                    Esperando la confirmación de Mercado Pago… esta página se actualiza sola.
                </p>
            </div>
        </div>
    @elseif ($compra->estado === EstadoCompra::Conflicto)
        <div class="alert alert-danger">
            El pago se aprobó, pero alguna butaca ya no estaba disponible, así que no se emitieron pasajes.
            Nos vamos a comunicar con vos para hacer el reintegro.
        </div>
    @elseif (in_array($compra->estado, [EstadoCompra::Vencida, EstadoCompra::Cancelada], true))
        <div class="alert alert-secondary">Esta compra no se pagó y las butacas se liberaron. Podés volver a elegirlas desde el listado de viajes.</div>
    @endif

    {{-- Pasajes emitidos (sólo existen si el pago fue aprobado) --}}
    @if ($compra->pasajes->isNotEmpty())
        <h2 class="titulo-seccion h5 mb-3">Pasajes</h2>
        <div class="row g-3 mb-4">
            @foreach ($compra->pasajes as $pasaje)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 va-viaje-card">
                        <div class="card-body">
                            <p class="small text-body-secondary mb-1">Pasaje <code>{{ $pasaje->codigo }}</code></p>
                            <h3 class="h6 mb-2">{{ $pasaje->viaje->origen->nombre }} <i class="bi bi-arrow-right"></i> {{ $pasaje->viaje->destino->nombre }}</h3>
                            <p class="small mb-2">
                                Sale {{ $pasaje->viaje->salida->isoFormat('ddd D/MM/YYYY HH:mm') }} h ·
                                Colectivo N.º {{ $pasaje->colectivo->numero_interno }} · Butaca {{ $pasaje->nro_butaca }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">@pesos($pasaje->precio)</span>
                                @if (Route::has('pasajes.pdf'))
                                    <a href="{{ route('pasajes.pdf', $pasaje) }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($compra->reservas->isNotEmpty())
        <h2 class="titulo-seccion h5 mb-3">Butacas de esta compra</h2>
        <ul class="list-group mb-4">
            @foreach ($compra->reservas as $reserva)
                <li class="list-group-item d-flex flex-wrap justify-content-between gap-2">
                    <span>
                        {{ $reserva->viaje->origen->nombre }} → {{ $reserva->viaje->destino->nombre }}
                        <span class="text-body-secondary small">· {{ $reserva->viaje->salida->format('d/m/Y H:i') }} · colectivo {{ $reserva->colectivo->numero_interno }} · butaca {{ $reserva->nro_butaca }}</span>
                    </span>
                    <span>@pesos($reserva->precio)</span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Intentos de pago informados por Mercado Pago --}}
    @if ($compra->pagos->isNotEmpty())
        <h2 class="titulo-seccion h6 mb-3">Pagos informados por Mercado Pago</h2>
        <div class="table-responsive">
            <table class="table table-sm small align-middle">
                <thead><tr><th>N.º de operación</th><th>Estado</th><th>Detalle</th><th>Medio</th><th class="text-end">Monto</th></tr></thead>
                <tbody>
                    @foreach ($compra->pagos as $pago)
                        <tr>
                            <td>{{ $pago->mp_payment_id ?? '—' }}</td>
                            <td><span class="badge text-bg-{{ $pago->aprobado() ? 'success' : ($pago->estado === 'rejected' ? 'danger' : 'warning') }}">{{ $pago->estado }}</span></td>
                            <td>{{ $pago->estado_detalle }}</td>
                            <td>{{ $pago->medio_pago }}</td>
                            <td class="text-end">@pesos($pago->monto)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
