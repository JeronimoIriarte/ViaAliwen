@extends('layouts.admin')

@section('titulo', "Viaje a {$viaje->destino->nombre}")

@section('contenido_admin')
@php $puedeEditar = auth()->user()->can('update', $viaje); @endphp

<a href="{{ route('admin.viajes.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a viajes</a>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $viaje->origen->nombre }} <i class="bi bi-arrow-right"></i> {{ $viaje->destino->nombre }}</h1>
        <p class="text-body-secondary mb-0">
            Sale el {{ $viaje->salida->isoFormat('dddd D [de] MMMM [a las] HH:mm') }} ·
            llega aprox. el {{ $viaje->llegada_estimada->isoFormat('dddd D [de] MMMM [a las] HH:mm') }}
        </p>
        <p class="mb-0">
            <span class="fw-semibold fs-5">@pesos($viaje->precio)</span>
            <span class="badge text-bg-secondary ms-2">{{ $viaje->estado() }}</span>
            @if ($viaje->salida->isFuture())
                <span class="badge text-bg-{{ $disponible ? 'success' : 'danger' }}">{{ $disponible ? 'Disponible para la venta' : 'No disponible' }}</span>
            @endif
        </p>
        <p class="small text-body-secondary mb-0">Cargado por {{ $viaje->creador->email }}</p>
    </div>

    @if ($puedeEditar)
        <div class="d-flex gap-2">
            <a href="{{ route('admin.viajes.edit', $viaje) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
            @can('delete', $viaje)
                <form method="POST" action="{{ route('admin.viajes.destroy', $viaje) }}" onsubmit="return confirm('¿Eliminar este viaje?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
                </form>
            @endcan
        </div>
    @else
        <span class="badge text-bg-light border"><i class="bi bi-lock me-1"></i>Sólo lo puede modificar quien lo cargó</span>
    @endif
</div>

<h2 class="h5 mb-3">Colectivos y ocupación</h2>
<div class="row g-4 mb-4">
    @forelse ($resumen as $fila)
        @php $c = $fila['colectivo']; @endphp
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="h6 mb-1">Colectivo N.º {{ $c->numero_interno }} <span class="text-body-secondary fw-normal">· {{ $c->patente }}</span></h3>
                            <span class="badge text-bg-{{ $c->esOperativo() ? 'success' : 'secondary' }}">{{ $c->estado->etiqueta() }}</span>
                            <span class="small text-body-secondary ms-2">
                                {{ $fila['vendidas']->count() }} vendidas · {{ $fila['reservadas']->count() }} reservadas · {{ $fila['libres'] }} libres
                            </span>
                        </div>
                        @if ($puedeEditar)
                            <form method="POST" action="{{ route('admin.viajes.colectivos.destroy', [$viaje, $c]) }}"
                                  onsubmit="return confirm('¿Quitar el colectivo {{ $c->numero_interno }} de este viaje?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($fila['vendidas']->isNotEmpty())
                                        title="{{ $fila['vendidas']->isNotEmpty() ? 'Tiene pasajes vendidos' : 'Quitar del viaje' }}">
                                    <i class="bi bi-x-lg me-1"></i>Quitar
                                </button>
                            </form>
                        @endif
                    </div>
                    <x-mapa-butacas :capacidad="$c->capacidad" :vendidas="$fila['vendidas']" :reservadas="$fila['reservadas']" />
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-warning mb-0">Este viaje no tiene colectivos asignados, por eso no se puede vender.</div></div>
    @endforelse
</div>

@if ($puedeEditar && $viaje->salida->isFuture())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h6 mb-3">Asignar otro colectivo</h2>
            @if ($colectivosLibres->isEmpty())
                <p class="text-body-secondary mb-0">No quedan colectivos operativos sin asignar.</p>
            @else
                <form method="POST" action="{{ route('admin.viajes.colectivos.store', $viaje) }}" class="d-flex flex-wrap gap-2 align-items-start" novalidate>
                    @csrf
                    <div>
                        <select name="colectivo_id" class="form-select @error('colectivo_id') is-invalid @enderror" aria-label="Colectivo">
                            @foreach ($colectivosLibres as $c)
                                <option value="{{ $c->id }}" @selected((int) old('colectivo_id') === $c->id)>N.º {{ $c->numero_interno }} · {{ $c->capacidad }} butacas</option>
                            @endforeach
                        </select>
                        @error('colectivo_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Asignar</button>
                </form>
            @endif
        </div>
    </div>
@endif

<h2 class="h5 mb-3">Pasajes vendidos <span class="badge text-bg-light">{{ $pasajes->count() }}</span></h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0 va-tabla">
            <thead class="table-light">
                <tr><th>Código</th><th>Pasajero</th><th>DNI</th><th class="text-center">Colectivo</th><th class="text-center">Butaca</th><th class="text-end">Precio pagado</th></tr>
            </thead>
            <tbody>
                @forelse ($pasajes as $pasaje)
                    <tr>
                        <td><code>{{ $pasaje->codigo }}</code></td>
                        <td><a href="{{ route('admin.viajeros.show', $pasaje->viajero) }}">{{ $pasaje->viajero->nombreCompleto() }}</a></td>
                        <td>{{ $pasaje->viajero->dni }}</td>
                        <td class="text-center">{{ $pasaje->colectivo->numero_interno }}</td>
                        <td class="text-center">{{ $pasaje->nro_butaca }}</td>
                        <td class="text-end">@pesos($pasaje->precio)</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Todavía no se vendieron pasajes de este viaje.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
