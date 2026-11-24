{{--
    Mapa de butacas de un colectivo en disposición 2 + pasillo + 2.
    Uso: <x-mapa-butacas :capacidad="40" :vendidas="[3, 4]" :reservadas="[10]" />
    Con :seleccionable="true" las butacas libres se pueden elegir (lo usa el selector de la Etapa 6).
--}}
@props(['capacidad', 'vendidas' => [], 'reservadas' => [], 'seleccionable' => false, 'nombre' => 'nro_butaca'])

@php
    $vendidas = collect($vendidas)->all();
    $reservadas = collect($reservadas)->all();
    $filas = (int) ceil($capacidad / 4);
@endphp

<div {{ $attributes->class(['va-mapa-butacas']) }} role="group" aria-label="Mapa de butacas">
    <div class="va-mapa-frente small text-body-secondary mb-2"><i class="bi bi-steering-wheel me-1"></i>Frente</div>
    @for ($fila = 0; $fila < $filas; $fila++)
        <div class="va-mapa-fila">
            @foreach ([1, 2, null, 3, 4] as $posicion)
                @if ($posicion === null)
                    <span class="va-mapa-pasillo" aria-hidden="true"></span>
                    @continue
                @endif
                @php
                    $numero = $fila * 4 + $posicion;
                    $estado = in_array($numero, $vendidas) ? 'vendida' : (in_array($numero, $reservadas) ? 'reservada' : 'libre');
                @endphp
                @if ($numero > $capacidad)
                    <span class="va-butaca va-butaca-vacia" aria-hidden="true"></span>
                @elseif ($seleccionable && $estado === 'libre')
                    <label class="va-butaca va-butaca-libre" title="Butaca {{ $numero }} libre" data-butaca="{{ $numero }}">
                        <input type="radio" class="visually-hidden" name="{{ $nombre }}" value="{{ $numero }}" required
                               aria-label="Butaca {{ $numero }}">
                        {{ $numero }}
                    </label>
                @else
                    <span class="va-butaca va-butaca-{{ $estado }}" title="Butaca {{ $numero }} {{ $estado }}" data-butaca="{{ $numero }}">{{ $numero }}</span>
                @endif
            @endforeach
        </div>
    @endfor
    <div class="d-flex flex-wrap gap-3 small mt-3">
        <span><span class="va-butaca va-butaca-libre va-butaca-mini"></span> Libre</span>
        <span><span class="va-butaca va-butaca-reservada va-butaca-mini"></span> Reservada</span>
        <span><span class="va-butaca va-butaca-vendida va-butaca-mini"></span> Vendida</span>
    </div>
</div>
