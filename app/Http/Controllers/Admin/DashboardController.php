<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EstadisticasService;
use Illuminate\View\View;

/**
 * Panel administrativo: estadísticas de ventas obtenidas de la base de datos con Eloquent.
 */
class DashboardController extends Controller
{
    public function index(EstadisticasService $estadisticas): View
    {
        return view('admin.dashboard', [
            'resumen' => $estadisticas->resumen(),
            'porDestino' => $estadisticas->ventasPorDestino(),
            'porOrigen' => $estadisticas->ventasPorOrigen(),
            'porViaje' => $estadisticas->pasajesPorViaje(10),
        ]);
    }
}
