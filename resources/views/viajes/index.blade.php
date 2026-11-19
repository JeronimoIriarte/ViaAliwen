@extends('layouts.app')

@section('titulo', 'Viajes disponibles')
@section('descripcion', 'Horarios, orígenes, destinos y precios de los viajes de VíaAliwén con butacas disponibles.')

@section('contenido')
<section class="va-encabezado py-5">
    <div class="container">
        <p class="va-eyebrow mb-2">Horarios y precios</p>
        <h1 class="mb-2">Viajes disponibles</h1>
        <p class="lead mb-0 opacity-75">Sólo se muestran viajes con butacas libres en colectivos operativos.</p>
    </div>
</section>

<div class="container py-4">
    {{--
        Búsqueda dinámica: con JavaScript, cada cambio consulta /viajes/buscar con fetch (JSON) y
        actualiza los resultados sin recargar. Sin JavaScript, el formulario GET funciona igual.
    --}}
    <form method="GET" action="{{ route('viajes.index') }}" id="form-busqueda" data-buscar-url="{{ route('viajes.buscar') }}"
          class="card border-0 shadow-sm mb-3" role="search" aria-label="Buscar viajes" novalidate>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="q" class="form-label small fw-semibold mb-1">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="60" autocomplete="off"
                               placeholder="Destino u origen, p. ej. Traful" class="form-control @error('q') is-invalid @enderror"
                               aria-describedby="estado-busqueda">
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="origen_id" class="form-label small fw-semibold mb-1">Desde</label>
                    <select id="origen_id" name="origen_id" class="form-select @error('origen_id') is-invalid @enderror">
                        <option value="">Cualquier origen</option>
                        @foreach ($destinos as $d)
                            <option value="{{ $d->id }}" @selected((int) ($filtros['origen_id'] ?? 0) === $d->id)>{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="destino_id" class="form-label small fw-semibold mb-1">Hacia</label>
                    <select id="destino_id" name="destino_id" class="form-select @error('destino_id') is-invalid @enderror">
                        <option value="">Cualquier destino</option>
                        @foreach ($destinos as $d)
                            <option value="{{ $d->id }}" @selected((int) ($filtros['destino_id'] ?? 0) === $d->id)>{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="fecha" class="form-label small fw-semibold mb-1">Fecha</label>
                    <input type="date" id="fecha" name="fecha" value="{{ $filtros['fecha'] ?? '' }}" min="{{ now()->format('Y-m-d') }}"
                           class="form-control @error('fecha') is-invalid @enderror">
                </div>
                <div class="col-sm-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Buscar</button>
                    <a href="{{ route('viajes.index') }}" class="btn btn-outline-secondary" title="Quitar filtros" data-limpiar>
                        <i class="bi bi-x-lg"></i><span class="visually-hidden">Quitar filtros</span>
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- Errores de validación (sin JS vienen de la sesión; con JS los completa la respuesta 422) --}}
    <div id="error-busqueda" class="alert alert-danger @if (! $errors->any()) d-none @endif" role="alert">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>

    <p id="estado-busqueda" class="text-body-secondary d-flex align-items-center gap-2" aria-live="polite">
        <span class="spinner-border spinner-border-sm text-primary d-none" role="status" data-cargando>
            <span class="visually-hidden">Buscando…</span>
        </span>
        <span data-texto>
            {{ $cantidad === 1 ? 'Se encontró 1 viaje' : "Se encontraron {$cantidad} viajes" }}@if (array_filter($filtros)) con los filtros elegidos @endif.
        </span>
    </p>

    <div id="resultados-viajes">
        @forelse ($viajesPorDia as $dia => $viajesDelDia)
            <section class="mb-4">
                <h2 class="va-dia pb-2 mb-3">{{ Str::ucfirst(\Illuminate\Support\Carbon::parse($dia)->isoFormat('dddd D [de] MMMM')) }}</h2>
                <div class="row g-4">
                    @foreach ($viajesDelDia as $viaje)
                        <div class="col-md-6 col-xl-4">
                            <x-viaje-card :viaje="$viaje" :libres="$libres[$viaje->id]" />
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            @include('viajes._sin-resultados')
        @endforelse
    </div>
</div>

{{-- Plantillas que usa busqueda-viajes.js para dibujar los resultados del JSON (mismo diseño que <x-viaje-card>) --}}
<template id="plantilla-dia">
    <section class="mb-4">
        <h2 class="va-dia pb-2 mb-3" data-campo="dia"></h2>
        <div class="row g-4" data-tarjetas></div>
    </section>
</template>

<template id="plantilla-viaje">
    <div class="col-md-6 col-xl-4">
        <article class="card va-card va-viaje-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <p class="small text-body-secondary mb-0" data-campo="origen"></p>
                        <h3 class="h5 mb-0"><i class="bi bi-arrow-down-short va-detalle" aria-hidden="true"></i><span data-campo="destino"></span></h3>
                    </div>
                    <span class="fs-5 fw-bold text-nowrap" style="color: var(--va-azul)" data-campo="precio"></span>
                </div>
                <dl class="row small mb-3 g-0">
                    <dt class="col-5 fw-normal text-body-secondary">Salida</dt>
                    <dd class="col-7 mb-1 fw-semibold" data-campo="salida"></dd>
                    <dt class="col-5 fw-normal text-body-secondary">Llegada estimada</dt>
                    <dd class="col-7 mb-1" data-campo="llegada"></dd>
                    <dt class="col-5 fw-normal text-body-secondary">Duración aprox.</dt>
                    <dd class="col-7 mb-0" data-campo="duracion"></dd>
                </dl>
                <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                    <span class="small" data-campo="libres"></span>
                    <a href="#" class="btn btn-sm btn-primary stretched-link" data-campo="url">Ver butacas</a>
                </div>
            </div>
        </article>
    </div>
</template>

<template id="plantilla-vacio">
    @include('viajes._sin-resultados')
</template>
@endsection
