{{-- Plantilla común de las páginas de error (403, 404, 419, 429). --}}
@extends('layouts.app')

@section('contenido')
<section class="container py-5 my-lg-4 text-center">
    <i class="bi @yield('icono', 'bi-signpost-split') display-1 va-detalle" aria-hidden="true"></i>
    <p class="display-6 fw-bold mt-3 mb-1" style="color: var(--va-azul)">@yield('codigo')</p>
    <h1 class="h3 mb-3">@yield('titulo_error')</h1>
    <p class="lead text-body-secondary mx-auto" style="max-width: 36rem">@yield('mensaje')</p>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
        <a href="{{ route('home') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Ir al inicio</a>
        <a href="{{ route('viajes.index') }}" class="btn btn-outline-success">Ver viajes disponibles</a>
    </div>
</section>
@endsection
