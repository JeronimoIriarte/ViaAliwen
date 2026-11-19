@extends('layouts.admin')

@section('titulo', "Editar colectivo {$colectivo->numero_interno}")

@section('contenido_admin')
<a href="{{ route('admin.colectivos.show', $colectivo) }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver al colectivo</a>
<h1 class="h3 mt-2 mb-4">Editar colectivo N.º {{ $colectivo->numero_interno }}</h1>

<div class="card border-0 shadow-sm mb-4" style="max-width: 48rem">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.colectivos.update', $colectivo) }}" novalidate>
            @csrf
            @method('PUT')
            @include('admin.colectivos._form')
            <p class="small text-body-secondary">
                Al reducir la capacidad se quitan las butacas de mayor número, siempre que no tengan pasajes vendidos.
                Para que deje de vender, cambiá el estado a "Dado de baja".
            </p>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
        </form>
    </div>
</div>

<div class="card border-danger-subtle" style="max-width: 48rem">
    <div class="card-body p-4">
        <h2 class="h6">Eliminar colectivo</h2>
        <p class="small text-body-secondary">Sólo se puede eliminar si nunca se asignó a un viaje. Si ya trabajó, dalo de baja.</p>
        <form method="POST" action="{{ route('admin.colectivos.destroy', $colectivo) }}"
              onsubmit="return confirm('¿Eliminar definitivamente el colectivo {{ $colectivo->numero_interno }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Eliminar</button>
        </form>
    </div>
</div>
@endsection
