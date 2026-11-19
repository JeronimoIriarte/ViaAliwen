@extends('layouts.app')

@section('titulo', "{$viaje->origen->nombre} → {$viaje->destino->nombre}")

@section('contenido')
@php $foto = $viaje->destino->portada; @endphp

<section class="va-encabezado va-encabezado-foto py-4"
         @if ($foto) style="background-image: linear-gradient(0deg, rgba(11,46,79,.92), rgba(11,46,79,.35)), url('{{ $foto->url() }}')" @endif>
    <div class="container">
        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="link-light">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('viajes.index') }}" class="link-light">Viajes</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $viaje->destino->nombre }}</li>
            </ol>
        </nav>
        <p class="mb-1 opacity-75">{{ $viaje->origen->nombre }}</p>
        <h1 class="mb-0"><i class="bi bi-arrow-right-short" aria-hidden="true"></i>{{ $viaje->destino->nombre }}</h1>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">
        {{-- Datos del viaje --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-body-secondary small mb-1">Precio por pasaje</p>
                    <p class="display-6 fw-bold mb-3" style="color: var(--va-azul)">@pesos($viaje->precio)</p>

                    <dl class="mb-3">
                        <dt class="small text-body-secondary fw-normal">Salida</dt>
                        <dd class="fw-semibold">{{ $viaje->salida->isoFormat('dddd D [de] MMMM, HH:mm') }} h</dd>
                        <dt class="small text-body-secondary fw-normal">Llegada estimada</dt>
                        <dd>{{ $viaje->llegada_estimada->isoFormat('dddd D [de] MMMM, HH:mm') }} h</dd>
                        <dt class="small text-body-secondary fw-normal">Duración aproximada</dt>
                        <dd class="mb-0">{{ $viaje->duracionTexto() }}</dd>
                    </dl>

                    @if ($disponible)
                        <div class="alert alert-success small mb-3"><i class="bi bi-check-circle me-1"></i>Hay butacas disponibles.</div>
                        @guest
                            <p class="small text-body-secondary mb-2">Para comprar necesitás una cuenta de viajero.</p>
                            <a href="{{ route('login', ['volver' => request()->getRequestUri()]) }}" class="btn btn-primary w-100 mb-2">Ingresá para comprar</a>
                            <a href="{{ route('registro') }}" class="btn btn-outline-success w-100">Crear cuenta</a>
                        @endguest
                        @auth
                            @if (auth()->user()->esViajero())
                                <p class="small mb-0"><i class="bi bi-hand-index me-1 va-detalle"></i>Elegí una butaca libre en el mapa y agregala al carrito.</p>
                            @else
                                <p class="small text-body-secondary mb-0">Los administradores no compran pasajes desde la web.</p>
                            @endif
                        @endauth
                    @else
                        <div class="alert alert-secondary small mb-0">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ $viaje->salida->isPast() ? 'Este viaje ya salió.' : 'No quedan butacas disponibles para este viaje.' }}
                        </div>
                    @endif
                </div>
            </div>
            <a href="{{ route('destinos.show', $viaje->destino) }}" class="d-inline-block mt-3 small">
                <i class="bi bi-images me-1"></i>Conocé {{ $viaje->destino->nombre }}
            </a>
        </div>

        {{-- Colectivos asociados y sus butacas --}}
        @php $puedeComprar = $disponible && auth()->user()?->esViajero(); @endphp
        <div class="col-lg-8" data-butacas-url="{{ route('viajes.butacas', $viaje) }}">
            <h2 class="titulo-seccion h4 mb-4">Colectivos y butacas</h2>

            @error('nro_butaca')
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}</div>
            @enderror
            <div class="alert alert-warning d-none" data-aviso-butaca role="status"></div>

            @forelse ($resumen as $fila)
                @php $c = $fila['colectivo']; @endphp
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h3 class="h6 mb-0">
                                <i class="bi bi-bus-front me-1 va-detalle" aria-hidden="true"></i>Colectivo N.º {{ $c->numero_interno }}
                                <span class="text-body-secondary fw-normal">· {{ $c->capacidad }} butacas</span>
                            </h3>
                            @if (! $c->esOperativo())
                                <span class="badge text-bg-secondary">Fuera de servicio</span>
                            @elseif ($fila['libres'] === 0)
                                <span class="badge text-bg-danger">Completo</span>
                            @else
                                <span class="badge va-badge-verde" data-libres="{{ $c->id }}">{{ $fila['libres'] }} libres</span>
                            @endif
                        </div>
                        @if ($c->esOperativo() && $puedeComprar && $fila['libres'] > 0)
                            {{-- Formulario por colectivo: los radios de cada mapa quedan agrupados por separado. --}}
                            <form method="POST" action="{{ route('carrito.store') }}" class="va-form-butaca">
                                @csrf
                                <input type="hidden" name="viaje_id" value="{{ $viaje->id }}">
                                <input type="hidden" name="colectivo_id" value="{{ $c->id }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-auto">
                                        <x-mapa-butacas :capacidad="$c->capacidad" :vendidas="$fila['vendidas']" :reservadas="$fila['reservadas']"
                                                        :seleccionable="true" data-colectivo="{{ $c->id }}" />
                                    </div>
                                    <div class="col-md">
                                        <p class="small text-body-secondary mb-2" data-seleccion>Ninguna butaca elegida todavía.</p>
                                        <button type="submit" class="btn btn-success" disabled>
                                            <i class="bi bi-cart-plus me-1"></i>Agregar al carrito
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @elseif ($c->esOperativo())
                            <x-mapa-butacas :capacidad="$c->capacidad" :vendidas="$fila['vendidas']" :reservadas="$fila['reservadas']"
                                            data-colectivo="{{ $c->id }}" />
                        @else
                            <p class="small text-body-secondary mb-0">Este colectivo no está vendiendo pasajes.</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-body-secondary">Este viaje todavía no tiene colectivos asignados.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
