@extends('layouts.admin')

@section('titulo', 'Editar viaje')

@section('contenido_admin')
<a href="{{ route('admin.viajes.show', $viaje) }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver al viaje</a>
<h1 class="h3 mt-2 mb-4">Editar viaje</h1>

<div class="card border-0 shadow-sm" style="max-width: 56rem">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.viajes.update', $viaje) }}" novalidate data-validacion-en-vivo>
            @csrf
            @method('PUT')
            @include('admin.viajes._form', ['soloPrecio' => $soloPrecio])
            <p class="small text-body-secondary">Los colectivos se asignan o quitan desde la ficha del viaje.</p>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
        </form>
    </div>
</div>
@endsection
