<?php

namespace Database\Seeders;

use App\Enums\EstadoColectivo;
use App\Services\ColectivoService;
use Illuminate\Database\Seeder;

/**
 * Flota de prueba. Las butacas se generan con ColectivoService (1..capacidad),
 * el mismo servicio que usa el panel administrativo.
 */
class ColectivoSeeder extends Seeder
{
    public function run(ColectivoService $servicio): void
    {
        $flota = [
            [101, 'AB123CD', 40, EstadoColectivo::Operativo],
            [102, 'AC456EF', 40, EstadoColectivo::Operativo],
            [103, 'AD789GH', 44, EstadoColectivo::Operativo],
            [104, 'AE012IJ', 28, EstadoColectivo::Operativo],
            [105, 'AF345KL', 20, EstadoColectivo::Operativo], // minibús: se usa para el "viaje completo"
            [106, 'AA678MN', 40, EstadoColectivo::Baja],      // fuera de servicio
        ];

        foreach ($flota as [$interno, $patente, $capacidad, $estado]) {
            $servicio->crear([
                'numero_interno' => $interno,
                'patente' => $patente,
                'capacidad' => $capacidad,
                'estado' => $estado,
            ]);
        }
    }
}
