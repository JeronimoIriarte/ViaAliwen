@extends('layouts.app')

@section('titulo', $destino->nombre)
@section('descripcion', Str::limit($destino->descripcion, 155))

@section('contenido')
<section class="va-encabezado py-4">
    <div class="container">
        <nav aria-label="Ruta de navegación">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="link-light">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('destinos.index') }}" class="link-light">Destinos</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $destino->nombre }}</li>
            </ol>
        </nav>
        <h1 class="mb-0">{{ $destino->nombre }}</h1>
    </div>
</section>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            {{-- Galería: carrusel de Bootstrap con miniaturas que saltan a cada foto --}}
            @if ($destino->imagenes->isNotEmpty())
                <div id="galeria" class="carousel slide va-galeria rounded-4 overflow-hidden shadow-sm" data-bs-ride="false" aria-label="Galería de {{ $destino->nombre }}">
                    <div class="carousel-inner">
                        @foreach ($destino->imagenes as $i => $imagen)
                            <div class="carousel-item @if ($i === 0) active @endif">
                                <img src="{{ $imagen->url() }}" class="d-block w-100" alt="{{ $imagen->epigrafe ?? $destino->nombre }}"
                                     @if ($i > 0) loading="lazy" @endif>
                                @if ($imagen->epigrafe)
                                    <div class="carousel-caption">
                                        <p class="mb-0 small fw-semibold">{{ $imagen->epigrafe }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($destino->imagenes->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#galeria" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Foto anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#galeria" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Foto siguiente</span>
                        </button>
                    @endif
                </div>

                @if ($destino->imagenes->count() > 1)
                    <div class="va-miniaturas d-flex flex-wrap gap-2 mt-3" aria-label="Miniaturas de la galería">
                        @foreach ($destino->imagenes as $i => $imagen)
                            <button type="button" data-bs-target="#galeria" data-bs-slide-to="{{ $i }}"
                                    class="@if ($i === 0) active @endif" @if ($i === 0) aria-current="true" @endif
                                    aria-label="Ver foto {{ $i + 1 }}">
                                <img src="{{ $imagen->url() }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif

                <p class="small text-body-secondary mt-3 mb-0">
                    <i class="bi bi-camera me-1"></i>Fotos:
                    {{ $destino->imagenes->pluck('credito')->filter()->unique()->implode(' — ') ?: 'VíaAliwén' }}
                </p>
            @else
                <div class="va-sin-foto rounded-4 d-flex flex-column align-items-center justify-content-center text-center p-5" style="min-height: 280px">
                    <i class="bi bi-image-alt display-4 mb-2" aria-hidden="true"></i>
                    <p class="mb-0">Pronto vamos a sumar fotos de {{ $destino->nombre }}.</p>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <h2 class="titulo-seccion h4 mb-3">Atractivos</h2>
            <p class="fs-6" style="line-height: 1.7">{{ $destino->descripcion }}</p>
            <a href="{{ route('viajes.index', ['destino_id' => $destino->id]) }}" class="btn btn-success mt-2">
                <i class="bi bi-calendar-week me-1"></i>Ver viajes a {{ $destino->nombre }}
            </a>
        </div>
    </div>

    <section class="mt-5">
        <h2 class="titulo-seccion h4 mb-4">Próximas salidas hacia {{ $destino->nombre }}</h2>
        <div class="row g-4">
            @forelse ($proximos as $viaje)
                <div class="col-md-6 col-lg-4">
                    <x-viaje-card :viaje="$viaje" :libres="$libres[$viaje->id]" />
                </div>
            @empty
                <div class="col-12">
                    <p class="text-body-secondary mb-0">Por ahora no hay viajes a la venta hacia este destino.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Resalta la miniatura de la foto que está mostrando el carrusel.
    document.getElementById('galeria')?.addEventListener('slid.bs.carousel', (evento) => {
        document.querySelectorAll('.va-miniaturas button').forEach((boton, indice) => {
            const activa = indice === evento.to;
            boton.classList.toggle('active', activa);
            activa ? boton.setAttribute('aria-current', 'true') : boton.removeAttribute('aria-current');
        });
    });
</script>
@endpush
