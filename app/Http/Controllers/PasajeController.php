<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Models\Pasaje;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga del pasaje en PDF (barryvdh/laravel-dompdf). Un PDF por pasaje.
 *
 * Los pasajes sólo existen después de que Mercado Pago aprobó el pago, así que no hay forma
 * de generar un PDF antes de pagar. Además se controla que el pasaje sea del viajero autenticado.
 */
class PasajeController extends Controller
{
    public function pdf(Pasaje $pasaje): Response
    {
        Gate::authorize('download', $pasaje); // PasajePolicy: sólo el titular

        $pasaje->load(['viajero.user', 'viaje.origen', 'viaje.destino', 'colectivo', 'compra.pagos']);

        // Doble control: el pasaje pertenece a una compra aprobada.
        abort_unless($pasaje->compra->estado === EstadoCompra::Aprobada, 404);

        $pago = $pasaje->compra->pagos->firstWhere('estado', 'approved');

        return Pdf::loadView('pdf.pasaje', ['pasaje' => $pasaje, 'pago' => $pago])
            ->setPaper('a4', 'portrait')
            ->setOption('isFontSubsettingEnabled', true) // incrusta sólo los caracteres usados: el PDF pesa mucho menos
            ->download("pasaje-{$pasaje->codigo}.pdf");
    }
}
