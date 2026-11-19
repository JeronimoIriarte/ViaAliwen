@extends('layouts.admin')

@section('titulo', 'Panel administrativo')

@section('contenido_admin')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Resumen de ventas</h1>
        <p class="text-body-secondary mb-0">Datos reales de la base al {{ now()->isoFormat('D [de] MMMM YYYY, HH:mm') }} h.</p>
    </div>
    <span class="small text-body-secondary">Sesión: {{ auth()->user()->email }}</span>
</div>

{{-- Indicadores --}}
<div class="row g-3 mb-4">
    @foreach ([
        ['Viajes registrados', number_format($resumen['viajes'], 0, ',', '.'), "{$resumen['programados']} programados", 'bi-signpost-split'],
        ['Viajes realizados', number_format($resumen['realizados'], 0, ',', '.'), "{$resumen['realizados_sin_pasajeros']} sin pasajeros", 'bi-flag'],
        ['Pasajes vendidos', number_format($resumen['pasajes'], 0, ',', '.'), 'con pago aprobado', 'bi-ticket-perforated'],
        ['Recaudación', \Illuminate\Support\Number::currency($resumen['recaudacion'], precision: 0), 'total de pasajes', 'bi-cash-stack'],
        ['Destinos', number_format($resumen['destinos'], 0, ',', '.'), 'localidades', 'bi-geo-alt'],
        ['Colectivos operativos', number_format($resumen['colectivos_operativos'], 0, ',', '.'), "{$resumen['colectivos_baja']} dados de baja", 'bi-bus-front'],
    ] as [$etiqueta, $valor, $detalle, $icono])
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="small text-body-secondary">{{ $etiqueta }}</span>
                        <i class="bi {{ $icono }} va-detalle" aria-hidden="true"></i>
                    </div>
                    <div class="fs-3 fw-bold mt-1 text-nowrap">{{ $valor }}</div>
                    <div class="small text-body-secondary">{{ $detalle }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<p class="small text-body-secondary">
    <i class="bi bi-info-circle me-1"></i>Un viaje cuenta como <strong>realizado</strong> cuando ya pasó su llegada estimada,
    vendiera o no pasajes. Los pasajes vendidos se cuentan sólo en compras con pago aprobado.
</p>

{{-- Ventas por destino y por origen --}}
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Ventas por destino</h2>
                <x-barras-ventas :filas="$porDestino" color="azul" />
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Ventas según el origen del viaje</h2>
                <x-barras-ventas :filas="$porOrigen" color="verde" />
            </div>
        </div>
    </div>
</div>

{{-- Pasajes vendidos por viaje --}}
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">Viajes con más pasajes vendidos</h2>
            <a href="{{ route('admin.viajes.index', ['estado' => 'todos']) }}" class="small">Ver los pasajes vendidos de todos los viajes</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 va-tabla">
                <thead>
                    <tr>
                        <th scope="col">Salida</th>
                        <th scope="col">Recorrido</th>
                        <th scope="col" class="text-center">Pasajes</th>
                        <th scope="col" class="w-25">Ocupación</th>
                        <th scope="col" class="text-end">Recaudación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($porViaje as $fila)
                        <tr>
                            <td class="text-nowrap small">{{ $fila['viaje']->salida->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('admin.viajes.show', $fila['viaje']) }}">{{ $fila['viaje']->origen->nombre }} → {{ $fila['viaje']->destino->nombre }}</a></td>
                            <td class="text-center">{{ $fila['pasajes'] }} / {{ $fila['capacidad'] }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2" title="{{ $fila['ocupacion'] }} % de ocupación">
                                    <div class="va-barra-pista flex-grow-1">
                                        <div class="va-barra va-barra-azul" style="width: {{ min(100, $fila['ocupacion']) }}%"></div>
                                    </div>
                                    <span class="small" style="min-width: 2.5rem">{{ $fila['ocupacion'] }} %</span>
                                </div>
                            </td>
                            <td class="text-end small text-nowrap">@pesos($fila['recaudacion'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
