{{--
    Campos compartidos por el alta y la edición de viajes.
    Si $soloPrecio es verdadero (el viaje ya tiene pasajes vendidos) los demás campos se muestran bloqueados.
--}}
@php
    $soloPrecio = $soloPrecio ?? false;
    $formatoFecha = fn ($fecha) => $fecha?->format('Y-m-d\TH:i');
@endphp

@if ($soloPrecio)
    <div class="alert alert-info small">
        <i class="bi bi-lock me-1"></i>Este viaje ya tiene pasajes vendidos: sólo se puede modificar el precio,
        que se aplica a las compras futuras. Origen, destino y horarios quedan bloqueados.
    </div>
@endif

<div class="row">
    @foreach (['origen_id' => 'Origen', 'destino_id' => 'Destino'] as $campo => $etiqueta)
        <div class="col-md-6 mb-3">
            <label for="{{ $campo }}" class="form-label fw-semibold">{{ $etiqueta }}</label>
            <select id="{{ $campo }}" name="{{ $campo }}" class="form-select @error($campo) is-invalid @enderror" @disabled($soloPrecio) required>
                <option value="">Elegí una localidad…</option>
                @foreach ($destinos as $d)
                    <option value="{{ $d->id }}" @selected((int) old($campo, $viaje->{$campo}) === $d->id)>{{ $d->nombre }}</option>
                @endforeach
            </select>
            @error($campo)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endforeach

    <div class="col-md-6">
        <x-campo nombre="salida" etiqueta="Fecha y hora de salida" tipo="datetime-local"
                 :valor="$formatoFecha($viaje->salida)" :disabled="$soloPrecio" required />
    </div>
    <div class="col-md-6">
        <x-campo nombre="llegada_estimada" etiqueta="Fecha y hora estimada de llegada" tipo="datetime-local"
                 :valor="$formatoFecha($viaje->llegada_estimada)" :disabled="$soloPrecio"
                 ayuda="Se carga a mano según el recorrido." required />
    </div>
    <div class="col-md-6">
        <x-campo nombre="precio" etiqueta="Precio del pasaje ($)" tipo="number" :valor="$viaje->precio"
                 min="0" step="0.01" required />
    </div>
</div>
