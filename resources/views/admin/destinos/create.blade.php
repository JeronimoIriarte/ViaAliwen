@extends('layouts.admin')

@section('titulo', 'Nuevo destino')

@section('contenido_admin')
<a href="{{ route('admin.destinos.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Volver a destinos</a>
<h1 class="h3 mt-2 mb-4">Nuevo destino</h1>

<div class="card border-0 shadow-sm" style="max-width: 48rem">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.destinos.store') }}" novalidate>
            @csrf
            @include('admin.destinos._form')
            <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i>Crear destino</button>
        </form>
    </div>
</div>
@endsection
