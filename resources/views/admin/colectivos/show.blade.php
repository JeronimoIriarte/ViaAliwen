@extends('layouts.admin')

@section('titulo', "Colectivo {$colectivo->numero_interno}")

@section('contenido_admin')
<a href="{{ route('admin.colectivos.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a colectivos</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
    <div>
        <h1 class="h3 mb-0">Colectivo N.º {{ $colectivo->numero_interno }}</h1>
        <p class="text-body-secondary mb-0">
            Patente <code>{{ $colectivo->patente }}</code> · {{ $colectivo->capacidad }} butacas ·
            <span class="badge text-bg-{{ $colectivo->esOperativo() ? 'success' : 'secondary' }}">{{ $colectivo->estado->etiqueta() }}</span>
        </p>
    </div>
    <a href="{{ route('admin.colectivos.edit', $colectivo) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Butacas</h2>
                <p class="small text-body-secondary">{{ $colectivo->butacas->count() }} butacas registradas para este colectivo.</p>
                <x-mapa-butacas :capacidad="$colectivo->butacas->count()" />
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Viajes programados</h2>
                @if ($viajes->isEmpty())
                    <p class="text-body-secondary mb-0">No tiene viajes programados.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle va-tabla mb-0">
                            <thead>
                                <tr><th>Salida</th><th>Recorrido</th><th class="text-end">Ocupación</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($viajes as $viaje)
                                    <tr>
                                        <td class="text-nowrap">{{ $viaje->salida->isoFormat('ddd D/MM HH:mm') }}</td>
                                        <td>
                                            <a href="{{ route('admin.viajes.show', $viaje) }}">{{ $viaje->origen->nombre }} → {{ $viaje->destino->nombre }}</a>
                                        </td>
                                        <td class="text-end text-nowrap">{{ $ocupacion[$viaje->id] }} / {{ $colectivo->capacidad }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
