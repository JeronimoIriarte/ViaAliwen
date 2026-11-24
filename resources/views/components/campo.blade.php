{{--
    Campo de formulario reutilizable: etiqueta + input + mensaje de error de validación.
    Uso: <x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" autocomplete="email" />

    - Los campos de contraseña llevan un botón para mostrar u ocultar lo escrito (resources/js/mostrar-clave.js).
    - El contenedor del error siempre existe (vacío si no hay error): la validación en vivo
      (resources/js/validacion-en-vivo.js) escribe ahí los mensajes mientras el usuario completa el formulario.
--}}
@props(['nombre', 'etiqueta', 'tipo' => 'text', 'valor' => null, 'ayuda' => null])

@php
    $esClave = $tipo === 'password';
    $describe = ($ayuda ? "{$nombre}-ayuda " : '')."{$nombre}-error";
@endphp

<div class="mb-3">
    <label for="{{ $nombre }}" class="form-label fw-semibold">{{ $etiqueta }}</label>
    {{-- En las contraseñas, el botón del ojo va dentro del campo (estilos .va-campo-clave en app.css). --}}
    @if ($esClave)
        <div class="va-campo-clave">
    @endif
        <input type="{{ $tipo }}" id="{{ $nombre }}" name="{{ $nombre }}"
               @unless ($esClave) value="{{ old($nombre, $valor) }}" @endunless
               aria-describedby="{{ $describe }}"
               @error($nombre) aria-invalid="true" @enderror
               {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($nombre)]) }}>
        @if ($esClave)
            <button type="button" class="va-ver-clave" data-mostrar-clave aria-controls="{{ $nombre }}"
                    aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        @endif
        <div class="invalid-feedback" id="{{ $nombre }}-error" data-error-para="{{ $nombre }}">@error($nombre){{ $message }}@enderror</div>
    @if ($esClave)
        </div>
    @endif
    @if ($ayuda)
        <div id="{{ $nombre }}-ayuda" class="form-text">{{ $ayuda }}</div>
    @endif
</div>
