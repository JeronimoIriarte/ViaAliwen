<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos de prueba en orden de dependencias:
     * usuarios -> destinos (con galerías) -> colectivos (con butacas) -> viajes -> ventas de demostración.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DestinoSeeder::class,
            ColectivoSeeder::class,
            ViajeSeeder::class,
            HistorialVentasSeeder::class,
        ]);
    }
}
