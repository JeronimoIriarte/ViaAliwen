@extends('layouts.admin')

@section('titulo', $viajero->nombreCompleto())

@section('contenido_admin')
<a href="{{ route('admin.viajeros.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a viajeros</a>
<h1 class="h3 mt-2 mb-1">{{ $viajero->nombreCompleto() }}</h1>
<p class="text-body-secondary mb-4">
    DNI {{ $viajero->dni }} · {{ $viajero->user->email }} · nacido/a el {{ $viajero->fecha_nacimiento->format('d/m/Y') }}
</p>

<h2 class="h5 mb-3">Compras <span class="badge text-bg-light">{{ $viajero->compras->count() }}</span></h2>
@forelse ($viajero->compras as $compra)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2">
            <span class="small">Compra <code>{{ Str::limit($compra->referencia, 8, '') }}</code> · {{ $compra->created_at->format('d/m/Y H:i') }}</span>
            <span>
                <span class="badge text-bg-{{ $compra->estado->color() }}">{{ $compra->estado->etiqueta() }}</span>
                <span class="fw-semibold ms-2">@pesos($compra->total)</span>
            </span>
        </div>
        @if ($compra->pasajes->isNotEmpty())
            <ul class="list-group list-group-flush">
                @foreach ($compra->pasajes as $pasaje)
                    <li class="list-group-item d-flex flex-wrap justify-content-between gap-2 small">
                        <span>
                            <code>{{ $pasaje->codigo }}</code> ·
                            <a href="{{ route('admin.viajes.show', $pasaje->viaje) }}">{{ $pasaje->viaje->origen->nombre }} → {{ $pasaje->viaje->destino->nombre }}</a>
                            · {{ $pasaje->viaje->salida->format('d/m/Y H:i') }}
                        </span>
                        <span>Colectivo {{ $pasaje->colectivo->numero_interno }} · butaca {{ $pasaje->nro_butaca }} · @pesos($pasaje->precio)</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@empty
    <p class="text-body-secondary">Este viajero todavía no realizó compras.</p>
@endforelse
@endsection
