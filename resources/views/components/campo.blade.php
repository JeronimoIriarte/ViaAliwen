{{--
    Campo de formulario reutilizable: etiqueta + input + mensaje de error de validación.
    Uso: <x-campo nombre="email" etiqueta="Correo electrónico" tipo="email" autocomplete="email" />
--}}
@props(['nombre', 'etiqueta', 'tipo' => 'text', 'valor' => null, 'ayuda' => null])

<div class="mb-3">
    <label for="{{ $nombre }}" class="form-label fw-semibold">{{ $etiqueta }}</label>
    <input type="{{ $tipo }}" id="{{ $nombre }}" name="{{ $nombre }}"
           @if ($tipo !== 'password') value="{{ old($nombre, $valor) }}" @endif
           @if ($ayuda) aria-describedby="{{ $nombre }}-ayuda" @endif
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($nombre)]) }}>
    @if ($ayuda)
        <div id="{{ $nombre }}-ayuda" class="form-text">{{ $ayuda }}</div>
    @endif
    @error($nombre)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
