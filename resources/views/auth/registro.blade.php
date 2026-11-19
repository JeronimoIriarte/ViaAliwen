@extends('layouts.app')

@section('titulo', 'Crear cuenta')

@section('contenido')
<div class="container py-5">
    <div class="card va-form-card mx-auto" style="max-width: 44rem">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Creá tu cuenta de viajero</h1>
            <p class="text-body-secondary mb-4">Tus datos figuran en cada pasaje, así que cargalos tal como aparecen en tu DNI.</p>

            <form method="POST" action="{{ route('registro.guardar') }}" novalidate>
                @csrf

                <h2 class="h6 text-uppercase va-detalle mb-3">Datos personales</h2>
                <div class="row">
                    <div class="col-md-6">
                        <x-campo nombre="nombre" etiqueta="Nombre" autocomplete="given-name" maxlength="80" required autofocus />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="apellido" etiqueta="Apellido" autocomplete="family-name" maxlength="80" required />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="dni" etiqueta="DNI" inputmode="numeric" maxlength="10" ayuda="Sin puntos, por ejemplo 35123456." required />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="fecha_nacimiento" etiqueta="Fecha de nacimiento" tipo="date" :max="now()->subDay()->format('Y-m-d')" required />
                    </div>
                </div>

                <h2 class="h6 text-uppercase va-detalle mt-2 mb-3">Datos de acceso</h2>
                <x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" autocomplete="email" required />
                <div class="row">
                    <div class="col-md-6">
                        <x-campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="new-password"
                                 ayuda="Mínimo 8 caracteres, con letras y números." required />
                    </div>
                    <div class="col-md-6">
                        <x-campo nombre="password_confirmation" etiqueta="Repetí la contraseña" tipo="password" autocomplete="new-password" required />
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 mt-2">
                    <i class="bi bi-person-plus me-1"></i>Crear cuenta
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                ¿Ya tenés cuenta? <a href="{{ route('login') }}" class="fw-semibold">Ingresá</a>
            </p>
        </div>
    </div>
</div>
@endsection
