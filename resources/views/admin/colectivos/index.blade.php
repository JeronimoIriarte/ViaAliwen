@extends('layouts.admin')

@section('titulo', 'Colectivos')

@section('contenido_admin')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0">Colectivos</h1>
        <p class="text-body-secondary mb-0">Flota propia. Un colectivo dado de baja no vende pasajes.</p>
    </div>
    <a href="{{ route('admin.colectivos.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Nuevo colectivo</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 va-tabla">
            <thead class="table-light">
                <tr>
                    <th>Interno</th>
                    <th>Patente</th>
                    <th class="text-center">Butacas</th>
                    <th>Estado</th>
                    <th class="text-center">Viajes programados</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($colectivos as $colectivo)
                    <tr>
                        <td class="fw-semibold">N.º {{ $colectivo->numero_interno }}</td>
                        <td><code>{{ $colectivo->patente }}</code></td>
                        <td class="text-center">{{ $colectivo->capacidad }}</td>
                        <td>
                            <span class="badge text-bg-{{ $colectivo->esOperativo() ? 'success' : 'secondary' }}">
                                {{ $colectivo->estado->etiqueta() }}
                            </span>
                        </td>
                        <td class="text-center">{{ $colectivo->viajes_programados_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.colectivos.show', $colectivo) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-grid-3x3-gap me-1"></i>Butacas
                            </a>
                            <a href="{{ route('admin.colectivos.edit', $colectivo) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Todavía no hay colectivos cargados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
