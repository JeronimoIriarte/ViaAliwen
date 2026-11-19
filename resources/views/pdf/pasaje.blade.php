{{--
    Plantilla del pasaje para DomPDF.
    DomPDF entiende CSS 2.1: se arma con tablas (no flexbox ni grid), estilos en la misma página
    y la fuente DejaVu Sans, que trae los acentos del español ("VíaAliwén", "Neuquén").
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pasaje {{ $pasaje->codigo }} · VíaAliwén</title>
    <style>
        @page { margin: 28px 32px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #1f2933; background: #ffffff; font-size: 11px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .pasaje { border: 1.5px solid #0b2e4f; border-radius: 10px; overflow: hidden; }
        .cabecera { background: #0b2e4f; color: #ffffff; }
        .cabecera td { padding: 16px 20px; vertical-align: middle; }
        .marca { font-size: 26px; font-weight: bold; letter-spacing: 0.5px; }
        .marca span { color: #7fd1a0; }
        .bajada { font-size: 10px; color: #c9d6e3; margin-top: 2px; }
        .numero { text-align: right; }
        .numero .etiqueta { color: #c9d6e3; }
        .numero .codigo { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        .franja { height: 5px; background: #2e7d4f; }
        .seccion { padding: 14px 20px; }
        .etiqueta { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #52606d; }
        .valor { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .recorrido td { vertical-align: top; }
        .ciudad { font-size: 22px; font-weight: bold; color: #0b2e4f; margin-top: 3px; }
        .flecha { text-align: center; font-size: 26px; color: #2e7d4f; width: 60px; vertical-align: middle !important; }
        .grilla td { border: 1px solid #d9e2ec; padding: 9px 12px; width: 25%; vertical-align: top; }
        .grilla .resaltado { background: #e8f1fa; }
        .grande { font-size: 20px; color: #1565a6; }
        .precio { font-size: 18px; color: #2e7d4f; }
        .talon { border-top: 2px dashed #9fb3c8; }
        .talon td { padding: 12px 20px; vertical-align: middle; }
        .talon .titulo { font-size: 9px; font-weight: bold; color: #0b2e4f; text-transform: uppercase; letter-spacing: 1px; }
        .codigo-talon { font-family: "DejaVu Sans Mono", monospace; font-size: 16px; font-weight: bold; letter-spacing: 3px; }
        .indicaciones { margin-top: 18px; font-size: 9.5px; color: #3e4c59; line-height: 1.5; }
        .indicaciones strong { color: #0b2e4f; }
        .pie { margin-top: 14px; font-size: 8px; color: #7b8794; text-align: center; }
    </style>
</head>
<body>
    @php
        $viaje = $pasaje->viaje;
        $viajero = $pasaje->viajero;
    @endphp

    <div class="pasaje">
        {{-- Cabecera con la empresa y el identificador del pasaje --}}
        <table class="cabecera">
            <tr>
                <td>
                    <div class="marca">Vía<span>Aliwén</span></div>
                    <div class="bajada">Transporte de pasajeros · Provincia del Neuquén</div>
                </td>
                <td class="numero">
                    <div class="etiqueta">Pasaje N.º</div>
                    <div class="codigo">{{ $pasaje->codigo }}</div>
                </td>
            </tr>
        </table>
        <div class="franja"></div>

        {{-- Origen y destino --}}
        <div class="seccion">
            <table class="recorrido">
                <tr>
                    <td>
                        <div class="etiqueta">Origen</div>
                        <div class="ciudad">{{ $viaje->origen->nombre }}</div>
                    </td>
                    <td class="flecha">&rarr;</td>
                    <td>
                        <div class="etiqueta">Destino</div>
                        <div class="ciudad">{{ $viaje->destino->nombre }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Datos del viaje, del pasajero y del pago --}}
        <div class="seccion" style="padding-top: 0">
            <table class="grilla">
                <tr>
                    <td class="resaltado">
                        <div class="etiqueta">Fecha de salida</div>
                        <div class="valor">{{ $viaje->salida->format('d/m/Y') }}</div>
                    </td>
                    <td class="resaltado">
                        <div class="etiqueta">Horario de salida</div>
                        <div class="valor">{{ $viaje->salida->format('H:i') }} h</div>
                    </td>
                    <td>
                        <div class="etiqueta">Fecha estimada de llegada</div>
                        <div class="valor">{{ $viaje->llegada_estimada->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Horario estimado de llegada</div>
                        <div class="valor">{{ $viaje->llegada_estimada->format('H:i') }} h</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="etiqueta">Pasajero</div>
                        <div class="valor">{{ $viajero->nombre }} {{ $viajero->apellido }}</div>
                    </td>
                    <td colspan="2">
                        <div class="etiqueta">DNI</div>
                        <div class="valor">{{ number_format((int) $viajero->dni, 0, ',', '.') }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="etiqueta">Colectivo N.º</div>
                        <div class="valor grande">{{ $pasaje->colectivo->numero_interno }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Butaca</div>
                        <div class="valor grande">{{ $pasaje->nro_butaca }}</div>
                    </td>
                    <td colspan="2">
                        <div class="etiqueta">Precio abonado</div>
                        <div class="valor precio">@pesos($pasaje->precio)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="etiqueta">Referencia de compra</div>
                        <div class="valor" style="font-size: 10px">{{ $pasaje->compra->referencia }}</div>
                        @if ($pago?->mp_payment_id)
                            <div class="etiqueta" style="margin-top: 4px">Operación Mercado Pago N.º {{ $pago->mp_payment_id }}</div>
                        @endif
                    </td>
                    <td colspan="2">
                        <div class="etiqueta">Fecha de emisión</div>
                        <div class="valor">{{ $pasaje->created_at->format('d/m/Y H:i') }} h</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Talón para el control al subir --}}
        <table class="talon">
            <tr>
                <td style="width: 34%">
                    <div class="titulo">Talón de embarque</div>
                    <div class="codigo-talon">{{ $pasaje->codigo }}</div>
                </td>
                <td style="width: 22%">
                    <div class="etiqueta">Salida</div>
                    <div class="valor">{{ $viaje->salida->format('d/m H:i') }}</div>
                </td>
                <td style="width: 22%">
                    <div class="etiqueta">Colectivo</div>
                    <div class="valor">{{ $pasaje->colectivo->numero_interno }}</div>
                </td>
                <td style="width: 22%">
                    <div class="etiqueta">Butaca</div>
                    <div class="valor">{{ $pasaje->nro_butaca }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="indicaciones">
        <strong>Importante:</strong> presentate en la terminal al menos 30 minutos antes de la salida con este pasaje
        (impreso o en el celular) y tu DNI. El pasaje es personal e intransferible y es válido sólo para el viaje,
        el colectivo y la butaca indicados. Los horarios de llegada son estimados y pueden variar según el estado
        de las rutas.
    </div>

    <div class="pie">
        VíaAliwén · Empresa ficticia creada para el Trabajo Final de Programación Web Dinámica · contacto@viaaliwen.test
    </div>
</body>
</html>
