@extends('layouts.app')

@section('titulo', 'Mis datos')

@section('contenido')
<div class="container py-5">
    <div class="card va-form-card mx-auto" style="max-width: 44rem">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Mis datos</h1>
            <p class="text-body-secondary mb-4">Estos datos se imprimen en tus pasajes.</p>

            <form method="POST" action="{{ route('perfil.update') }}" novalidate data-validacion-en-vivo>
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <x-campo nombre="nombre" etiqueta="Nombre" :valor="$viajero->nombre" maxlength="80" required />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="apellido" etiqueta="Apellido" :valor="$viajero->apellido" maxlength="80" required />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="fecha_nacimiento" etiqueta="Fecha de nacimiento" tipo="date"
                                 :valor="$viajero->fecha_nacimiento->format('Y-m-d')" :max="now()->subDay()->format('Y-m-d')" required />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="dni_lectura">DNI</label>
                        <input type="text" class="form-control" id="dni_lectura" value="{{ $viajero->dni }}" readonly disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="email_lectura">Correo electrónico</label>
                        <input type="email" class="form-control" id="email_lectura" value="{{ $usuario->email }}" readonly disabled>
                    </div>
                    <div class="col-12">
                        <p class="form-text mt-0">El DNI y el correo identifican tu cuenta y no se pueden modificar desde acá.</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Guardar cambios
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
