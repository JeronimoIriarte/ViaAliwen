{{-- Campos compartidos por el alta y la edición de colectivos. --}}
<div class="row">
    <div class="col-md-6">
        <x-campo nombre="numero_interno" etiqueta="Número interno" tipo="number" :valor="$colectivo->numero_interno"
                 min="1" max="9999" ayuda="Es el número de colectivo que figura en el pasaje." required />
    </div>
    <div class="col-md-6">
        <x-campo nombre="patente" etiqueta="Patente" :valor="$colectivo->patente" maxlength="9"
                 ayuda="Formato ABC123 o AB123CD." required />
    </div>
    <div class="col-md-6">
        <x-campo nombre="capacidad" etiqueta="Capacidad (butacas)" tipo="number" :valor="$colectivo->capacidad"
                 min="10" max="60" ayuda="Se generan las butacas numeradas de 1 a la capacidad." required />
    </div>
    <div class="col-md-6 mb-3">
        <label for="estado" class="form-label fw-semibold">Estado</label>
        <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror" required aria-describedby="estado-error">
            @foreach (\App\Enums\EstadoColectivo::cases() as $estado)
                <option value="{{ $estado->value }}" @selected(old('estado', $colectivo->estado?->value) === $estado->value)>
                    {{ $estado->etiqueta() }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="estado-error" data-error-para="estado">@error('estado'){{ $message }}@enderror</div>
    </div>
</div>
