@extends('layouts.app')

@section('contenido')
{{-- Portada con buscador de viajes --}}
<section class="va-portada py-5" style="--va-portada-img: url('{{ asset('img/hero.jpg') }}')">
    <div class="container py-lg-5">
        <p class="va-eyebrow mb-2">Pasajes de colectivo · Provincia del Neuquén</p>
        <h1 class="display-5 mb-3" style="max-width: 40rem">Viajá a los lagos, volcanes y termas del Neuquén</h1>
        <p class="lead mb-4">Elegí tu destino y tu butaca, pagá online con Mercado Pago y descargá tu pasaje en PDF al instante.</p>

        <form method="GET" action="{{ route('viajes.index') }}" class="va-buscador p-3 p-md-4" role="search" aria-label="Buscar viajes">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="buscar_origen" class="form-label">Desde</label>
                    <select id="buscar_origen" name="origen_id" class="form-select">
                        <option value="">Cualquier origen</option>
                        @foreach ($destinos as $d)
                            <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="buscar_destino" class="form-label">Hacia</label>
                    <select id="buscar_destino" name="destino_id" class="form-select">
                        <option value="">Cualquier destino</option>
                        @foreach ($destinos as $d)
                            <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-7 col-md-2">
                    <label for="buscar_fecha" class="form-label">Fecha</label>
                    <input type="date" id="buscar_fecha" name="fecha" class="form-control" min="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-sm-5 col-md-2 d-grid">
                    <button type="submit" class="btn btn-success py-2"><i class="bi bi-search me-1"></i>Buscar</button>
                </div>
            </div>
        </form>
    </div>
</section>

{{-- Destinos destacados --}}
<section class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="titulo-seccion mb-1">Destinos destacados</h2>
            <p class="text-body-secondary mb-0">Paisajes de cordillera, lagos y termas a pocas horas de viaje.</p>
        </div>
        <a href="{{ route('destinos.index') }}" class="btn btn-outline-success">Ver todos los destinos</a>
    </div>
    <div class="row g-4">
        @foreach ($destacados as $destino)
            <div class="col-sm-6 col-lg-4">
                <x-destino-card :destino="$destino" />
            </div>
        @endforeach
    </div>
</section>

{{-- Próximos viajes a la venta --}}
<section class="va-franja py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h2 class="titulo-seccion mb-1">Próximas salidas</h2>
                <p class="text-body-secondary mb-0">Viajes con butacas disponibles.</p>
            </div>
            <a href="{{ route('viajes.index') }}" class="btn btn-primary">Ver todos los viajes</a>
        </div>
        <div class="row g-4">
            @forelse ($proximos as $viaje)
                <div class="col-md-6 col-lg-4">
                    <x-viaje-card :viaje="$viaje" :libres="$libres[$viaje->id]" />
                </div>
            @empty
                <div class="col-12"><p class="text-body-secondary">No hay viajes a la venta en este momento.</p></div>
            @endforelse
        </div>
    </div>
</section>

{{-- Por qué viajar con nosotros --}}
<section class="container py-5">
    <h2 class="titulo-seccion mb-4">Por qué viajar con VíaAliwén</h2>
    <div class="row g-4">
        @foreach ([
            ['bi-bus-front', 'Flota propia', 'Viajás siempre en nuestros colectivos: sin intermediarios.'],
            ['bi-grid-3x3-gap', 'Elegís tu butaca', 'Mirá el mapa del colectivo y reservá el asiento que prefieras.'],
            ['bi-shield-check', 'Pago seguro', 'Pagás con Mercado Pago; tu pasaje se emite cuando el pago se aprueba.'],
            ['bi-file-earmark-pdf', 'Pasaje en PDF', 'Descargalo desde "Mis compras" cuando quieras, uno por cada viaje.'],
        ] as [$icono, $titulo, $texto])
            <div class="col-sm-6 col-lg-3 va-beneficio">
                <i class="bi {{ $icono }}" aria-hidden="true"></i>
                <h3 class="h6 mt-2 mb-1">{{ $titulo }}</h3>
                <p class="small text-body-secondary mb-0">{{ $texto }}</p>
            </div>
        @endforeach
    </div>

    @guest
        <div class="card border-0 va-encabezado mt-5">
            <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h4 mb-1">¿Listo para viajar?</h2>
                    <p class="mb-0 opacity-75">Creá tu cuenta gratis para comprar pasajes y guardar tu historial.</p>
                </div>
                <a href="{{ route('registro') }}" class="btn btn-success btn-lg">Crear cuenta</a>
            </div>
        </div>
    @endguest
</section>
@endsection
