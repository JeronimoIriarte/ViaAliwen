@extends('layouts.admin')

@section('titulo', 'Destinos')

@section('contenido_admin')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0">Destinos</h1>
        <p class="text-body-secondary mb-0">Localidades neuquinas: destinos de viaje y también orígenes.</p>
    </div>
    <a href="{{ route('admin.destinos.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Nuevo destino</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 va-tabla">
            <thead class="table-light">
                <tr>
                    <th>Destino</th>
                    <th class="text-center">Fotos</th>
                    <th class="text-center">Viajes hacia</th>
                    <th class="text-center">Viajes desde</th>
                    <th class="text-center">Destacado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($destinos as $destino)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($destino->portada)
                                    <img src="{{ $destino->portada->url() }}" alt="" class="rounded object-fit-cover" width="64" height="44">
                                @else
                                    <span class="rounded bg-body-secondary d-inline-flex align-items-center justify-content-center" style="width:64px;height:44px">
                                        <i class="bi bi-image text-body-secondary"></i>
                                    </span>
                                @endif
                                <span class="fw-semibold">{{ $destino->nombre }}</span>
                            </div>
                        </td>
                        <td class="text-center">{{ $destino->imagenes_count }}</td>
                        <td class="text-center">{{ $destino->viajes_count }}</td>
                        <td class="text-center">{{ $destino->viajes_desde_count }}</td>
                        <td class="text-center">
                            @if ($destino->destacado)
                                <i class="bi bi-star-fill va-detalle" title="Destacado en el inicio"></i>
                            @else
                                <span class="text-body-tertiary">—</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.destinos.edit', $destino) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                            <form method="POST" action="{{ route('admin.destinos.destroy', $destino) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar el destino {{ $destino->nombre }} y sus fotos?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        @disabled($destino->viajes_count + $destino->viajes_desde_count > 0)
                                        title="{{ $destino->viajes_count + $destino->viajes_desde_count > 0 ? 'Tiene viajes asociados' : 'Eliminar' }}">
                                    <i class="bi bi-trash"></i><span class="visually-hidden">Eliminar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Todavía no hay destinos cargados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
