<?php

namespace Database\Seeders;

use App\Models\Colectivo;
use App\Models\Destino;
use App\Models\User;
use App\Models\Viaje;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Viajes de prueba: realizados (últimos 60 días) y programados (próximos 20 días),
 * más tres casos especiales para demostrar las reglas de disponibilidad.
 *
 * Nota: acá la llegada estimada se arma sumando una duración sólo para generar datos;
 * en el panel administrativo la llegada se carga a mano, como pide el enunciado.
 */
class ViajeSeeder extends Seeder
{
    /** [origen, destino, hora de salida, minutos de viaje, precio] */
    private const RUTAS = [
        ['Neuquén Capital', 'San Martín de los Andes', '07:00', 360, 38500],
        ['Neuquén Capital', 'Villa La Angostura', '08:00', 390, 41000],
        ['Neuquén Capital', 'Junín de los Andes', '09:00', 300, 34000],
        ['Neuquén Capital', 'Villa Traful', '07:30', 360, 39500],
        ['Neuquén Capital', 'Caviahue', '08:30', 360, 36000],
        ['Neuquén Capital', 'Copahue', '08:00', 390, 37500],
        ['Neuquén Capital', 'Aluminé', '10:00', 330, 33000],
        ['Neuquén Capital', 'Chos Malal', '14:00', 300, 30000],
        ['Neuquén Capital', 'Zapala', '16:00', 165, 15500],
        ['Zapala', 'Caviahue', '09:30', 180, 18000],
        ['Zapala', 'Aluminé', '11:00', 150, 14500],
        ['San Martín de los Andes', 'Villa La Angostura', '10:00', 165, 16000],
        ['San Martín de los Andes', 'Neuquén Capital', '15:00', 360, 38500],
        ['Junín de los Andes', 'San Martín de los Andes', '18:00', 45, 6500],
        ['Villa La Angostura', 'Neuquén Capital', '14:30', 390, 41000],
        ['Chos Malal', 'Neuquén Capital', '07:00', 300, 30000],
    ];

    private $destinos;

    private User $admin;

    private User $ventas;

    public function run(): void
    {
        $this->destinos = Destino::pluck('id', 'nombre');
        $this->admin = User::where('email', 'admin@viaaliwen.test')->firstOrFail();
        $this->ventas = User::where('email', 'ventas@viaaliwen.test')->firstOrFail();
        $flota = Colectivo::orderBy('numero_interno')->get()->keyBy('numero_interno');
        $hoy = CarbonImmutable::today();

        // --- Casos especiales (se crean primero para que tengan prioridad en la asignación) ---

        // 1) Viaje que se completará: sólo el minibús 105 (20 butacas). HistorialVentasSeeder vende todas.
        $this->crearViaje(['Neuquén Capital', 'Villa Traful', '06:00', 360, 39500], $hoy->addDays(3), [$flota[105]]);

        // 2) Viaje cuyo único colectivo quedó dado de BAJA: no debe mostrarse como disponible.
        $this->crearViaje(['Neuquén Capital', 'Chos Malal', '08:00', 300, 30000], $hoy->addDays(4), [$flota[106]]);

        // 3) Viaje de alta demanda con dos colectivos asignados (relación N:M).
        $this->crearViaje(['Neuquén Capital', 'San Martín de los Andes', '06:30', 360, 38500], $hoy->addDays(6), [$flota[101], $flota[102]]);

        // --- Servicios regulares: los colectivos 101 a 104 rotan sin superponerse en el tiempo ---
        $regulares = $flota->whereIn('numero_interno', [101, 102, 103, 104])->values();

        for ($dia = -60; $dia <= 20; $dia++) {
            if ($dia === 0 || ($dia < 0 && $dia % 2 !== 0)) {
                continue; // en el pasado, un día sí y otro no; hoy no hay salidas cargadas
            }

            for ($k = 0; $k < 3; $k++) {
                $ruta = self::RUTAS[(abs($dia) * 3 + $k) % count(self::RUTAS)];
                $colectivo = $this->primerColectivoLibre($regulares, $hoy->addDays($dia), $ruta);

                if ($colectivo) {
                    $this->crearViaje($ruta, $hoy->addDays($dia), [$colectivo]);
                }
            }
        }
    }

    private function crearViaje(array $ruta, CarbonImmutable $dia, array $colectivos): Viaje
    {
        [$origen, $destino, $hora, $minutos, $precio] = $ruta;
        [$salida, $llegada] = $this->horarios($dia, $hora, $minutos);

        // Los viajes que salen de Neuquén los carga "admin"; el resto, "ventas".
        $creador = $origen === 'Neuquén Capital' ? $this->admin : $this->ventas;

        $viaje = $creador->viajesCargados()->create([
            'origen_id' => $this->destinos[$origen],
            'destino_id' => $this->destinos[$destino],
            'precio' => $precio,
            'salida' => $salida,
            'llegada_estimada' => $llegada,
        ]);

        // attach(): asociación en la tabla intermedia colectivo_viaje.
        $viaje->colectivos()->attach(collect($colectivos)->pluck('id'));

        return $viaje;
    }

    /** Busca un colectivo sin otro viaje que se superponga con el horario pedido. */
    private function primerColectivoLibre($colectivos, CarbonImmutable $dia, array $ruta): ?Colectivo
    {
        [$salida, $llegada] = $this->horarios($dia, $ruta[2], $ruta[3]);

        return $colectivos->first(fn (Colectivo $c) => ! $c->viajes()
            ->where('salida', '<', $llegada)
            ->where('llegada_estimada', '>', $salida)
            ->exists());
    }

    private function horarios(CarbonImmutable $dia, string $hora, int $minutos): array
    {
        [$h, $m] = explode(':', $hora);
        $salida = $dia->setTime((int) $h, (int) $m);

        return [$salida, $salida->addMinutes($minutos)];
    }
}
