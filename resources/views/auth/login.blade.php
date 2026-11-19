@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
<div class="container py-5">
    <div class="card va-form-card mx-auto">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Ingresá a tu cuenta</h1>
            <p class="text-body-secondary mb-4">Para comprar pasajes y ver tus compras.</p>

            <form method="POST" action="{{ route('login.intentar') }}" novalidate>
                @csrf

                <x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" autocomplete="email" required autofocus />
                <x-campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required />

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="recordarme" id="recordarme" value="1" @checked(old('recordarme'))>
                    <label class="form-check-label" for="recordarme">Mantener la sesión iniciada</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                ¿Todavía no tenés cuenta? <a href="{{ route('registro') }}" class="fw-semibold">Registrate</a>
            </p>
        </div>
    </div>
</div>
@endsection
