@extends('layouts.admin')

@section('titulo', 'Viajeros')

@section('contenido_admin')
<h1 class="h3 mb-0">Viajeros</h1>
<p class="text-body-secondary">Clientes registrados. Sus datos personales sólo los modifica cada viajero.</p>

<form method="GET" action="{{ route('admin.viajeros.index') }}" class="d-flex gap-2 mb-3" role="search" style="max-width: 32rem">
    <input type="search" name="q" value="{{ $busqueda }}" class="form-control" placeholder="Buscar por nombre, apellido, DNI o correo" aria-label="Buscar viajeros">
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i><span class="visually-hidden">Buscar</span></button>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 va-tabla">
            <thead class="table-light">
                <tr><th>Apellido y nombre</th><th>DNI</th><th>Correo</th><th class="text-center">Pasajes</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($viajeros as $viajero)
                    <tr>
                        <td class="fw-semibold">{{ $viajero->apellido }}, {{ $viajero->nombre }}</td>
                        <td>{{ $viajero->dni }}</td>
                        <td class="small">{{ $viajero->user->email }}</td>
                        <td class="text-center">{{ $viajero->pasajes_count }}</td>
                        <td class="text-end"><a href="{{ route('admin.viajeros.show', $viajero) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No se encontraron viajeros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $viajeros->links() }}</div>
@endsection
