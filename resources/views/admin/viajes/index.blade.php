@extends('layouts.admin')

@section('titulo', 'Viajes')

@section('contenido_admin')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0">Viajes</h1>
        <p class="text-body-secondary mb-0">Programados, realizados y disponibilidad de butacas.</p>
    </div>
    <a href="{{ route('admin.viajes.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Nuevo viaje</a>
</div>

<form method="GET" action="{{ route('admin.viajes.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-end">
        <div>
            <label class="form-label small fw-semibold mb-1" for="estado">Mostrar</label>
            <select id="estado" name="estado" class="form-select">
                @foreach (['programados' => 'Programados', 'realizados' => 'Realizados', 'todos' => 'Todos'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected($estado === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small fw-semibold mb-1" for="destino_id">Destino</label>
            <select id="destino_id" name="destino_id" class="form-select">
                <option value="">Todos los destinos</option>
                @foreach ($destinos as $d)
                    <option value="{{ $d->id }}" @selected($destinoId === $d->id)>{{ $d->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 va-tabla">
            <thead class="table-light">
                <tr>
                    <th>Salida</th>
                    <th>Recorrido</th>
                    <th class="text-end">Precio</th>
                    <th class="text-center">Vendidos</th>
                    <th class="text-center">Libres</th>
                    <th>Estado</th>
                    <th>Cargado por</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($viajes as $viaje)
                    <tr>
                        <td class="text-nowrap">{{ $viaje->salida->isoFormat('ddd D/MM/YY HH:mm') }}</td>
                        <td>{{ $viaje->origen->nombre }} <i class="bi bi-arrow-right text-body-tertiary"></i> {{ $viaje->destino->nombre }}</td>
                        <td class="text-end text-nowrap">@pesos($viaje->precio)</td>
                        <td class="text-center">{{ $viaje->pasajes_count }}</td>
                        <td class="text-center">
                            @if ($viaje->salida->isPast())
                                <span class="text-body-tertiary">—</span>
                            @elseif ($libres[$viaje->id] === 0)
                                <span class="badge text-bg-danger">Sin lugar</span>
                            @else
                                {{ $libres[$viaje->id] }}
                            @endif
                        </td>
                        <td>
                            @php $estadoViaje = $viaje->estado(); @endphp
                            <span class="badge text-bg-{{ $estadoViaje === 'Programado' ? 'primary' : ($estadoViaje === 'En viaje' ? 'warning' : 'secondary') }}">{{ $estadoViaje }}</span>
                        </td>
                        <td class="small text-body-secondary">{{ $viaje->creador->email }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.viajes.show', $viaje) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-body-secondary py-4">No hay viajes con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $viajes->links() }}</div>
@endsection
