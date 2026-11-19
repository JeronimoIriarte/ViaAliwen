{{--
    Gráfico de barras horizontales (una sola serie) armado con HTML y CSS, con los valores escritos
    al lado de cada barra: se lee también como tabla. El color identifica la serie; el texto va en color de texto.
    Uso: <x-barras-ventas :filas="$porDestino" color="azul" />   (cada fila: nombre, pasajes, recaudacion)
--}}
@props(['filas', 'color' => 'azul'])

@php $maximo = max(1, $filas->max('pasajes')); @endphp

<table class="table table-sm align-middle mb-0 va-tabla va-barras">
    <thead>
        <tr>
            <th scope="col">Localidad</th>
            <th scope="col" class="w-50">Pasajes vendidos</th>
            <th scope="col" class="text-end">Recaudación</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($filas as $fila)
            <tr>
                <th scope="row" class="fw-semibold text-nowrap">{{ $fila['nombre'] }}</th>
                <td>
                    <div class="d-flex align-items-center gap-2"
                         title="{{ $fila['nombre'] }}: {{ number_format($fila['pasajes'], 0, ',', '.') }} pasajes">
                        <div class="va-barra-pista flex-grow-1">
                            <div class="va-barra va-barra-{{ $color }}" style="width: {{ round($fila['pasajes'] * 100 / $maximo, 1) }}%"></div>
                        </div>
                        <span class="small text-nowrap" style="min-width: 3rem">{{ number_format($fila['pasajes'], 0, ',', '.') }}</span>
                    </div>
                </td>
                <td class="text-end text-nowrap small">@pesos($fila['recaudacion'])</td>
            </tr>
        @endforeach
    </tbody>
</table>
