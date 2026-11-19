@extends('layouts.app')

@section('titulo', 'Destinos')
@section('descripcion', 'Destinos de VíaAliwén en la provincia del Neuquén: lagos, volcanes, termas y ciudades.')

@section('contenido')
<section class="va-encabezado py-5">
    <div class="container">
        <p class="va-eyebrow mb-2">Provincia del Neuquén</p>
        <h1 class="mb-2">Nuestros destinos</h1>
        <p class="lead mb-0 opacity-75">Conocé los atractivos de cada lugar y mirá sus galerías de fotos.</p>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4">
        @foreach ($destinos as $destino)
            <div class="col-sm-6 col-lg-4">
                <x-destino-card :destino="$destino" :viajes="$viajesPorDestino[$destino->id] ?? 0" />
            </div>
        @endforeach
    </div>
</section>
@endsection
