<?php

namespace Database\Seeders;

use App\Enums\EstadoCompra;
use App\Models\Colectivo;
use App\Models\Pasaje;
use App\Models\Viaje;
use App\Models\Viajero;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ventas de DEMOSTRACIÓN para que el panel de estadísticas y los mapas de butacas tengan datos.
 * Son datos de prueba: no pasaron por Mercado Pago (pagos.mp_payment_id queda en NULL y el
 * medio de pago figura como "datos de prueba"). Las compras reales sólo generan pasajes
 * después de verificar el pago aprobado.
 */
class HistorialVentasSeeder extends Seeder
{
    /** @var array<int, array<int, int>> viaje_id => [viajero_id => true]: un viajero no repite viaje */
    private array $pasajerosPorViaje = [];

    /** @var array<string, bool> "viaje-colectivo-butaca" ya vendidas */
    private array $vendidas = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->comprasDeViajerosConocidos();
            $this->completarViajeLleno();
            $this->ventasAleatorias();
        });
    }

    /** Historial predecible para las cuentas de prueba del README (Ana queda sin compras). */
    private function comprasDeViajerosConocidos(): void
    {
        $lucia = Viajero::where('dni', '35123456')->firstOrFail();
        $martin = Viajero::where('dni', '40234567')->firstOrFail();

        // Lucía: una compra con ida y vuelta ya realizadas (una compra puede incluir varios viajes).
        $ida = $this->viajeDeRuta('Neuquén Capital', 'San Martín de los Andes', pasado: true);
        $vuelta = Viaje::realizados()->where('salida', '>', $ida->llegada_estimada)
            ->whereHas('origen', fn ($q) => $q->where('nombre', 'San Martín de los Andes'))
            ->whereHas('destino', fn ($q) => $q->where('nombre', 'Neuquén Capital'))
            ->orderBy('salida')->first();
        $this->vender($lucia, array_filter([$ida, $vuelta]), $ida->salida->subDays(10));

        // Lucía: un viaje próximo.
        $proximo = $this->viajeDeRuta('Neuquén Capital', 'Villa La Angostura', pasado: false);
        $this->vender($lucia, [$proximo], CarbonImmutable::now()->subDays(2));

        // Martín: un viaje realizado.
        $zapala = $this->viajeDeRuta('Neuquén Capital', 'Zapala', pasado: true);
        $this->vender($martin, [$zapala], $zapala->salida->subDays(5));
    }

    /** Vende todas las butacas de los viajes programados del minibús 105 (caso "viaje completo"). */
    private function completarViajeLleno(): void
    {
        $minibus = Colectivo::where('numero_interno', 105)->firstOrFail();

        foreach ($minibus->viajes()->programados()->get() as $viaje) {
            foreach ($this->compradoresAleatorios($viaje, $minibus->capacidad) as $comprador) {
                $this->vender($comprador, [$viaje], CarbonImmutable::now()->subDays(random_int(1, 7)), $minibus);
            }
        }
    }

    /** Ocupación aleatoria: alta en viajes realizados, parcial en los programados. */
    private function ventasAleatorias(): void
    {
        $viajes = Viaje::with('colectivos')
            ->whereDoesntHave('colectivos', fn ($q) => $q->where('numero_interno', 105))
            ->get();

        foreach ($viajes as $viaje) {
            foreach ($viaje->colectivos as $colectivo) {
                if (! $colectivo->esOperativo()) {
                    continue; // en un colectivo dado de baja no se vende
                }

                $porcentaje = $viaje->salida->isPast() ? random_int(35, 90) : random_int(0, 45);
                $cantidad = intdiv($colectivo->capacidad * $porcentaje, 100);

                foreach ($this->compradoresAleatorios($viaje, $cantidad) as $comprador) {
                    $fecha = $viaje->salida->isPast()
                        ? $viaje->salida->subHours(random_int(2, 24 * 20))
                        : CarbonImmutable::now()->subHours(random_int(1, 24 * 10));
                    $this->vender($comprador, [$viaje], $fecha, $colectivo);
                }
            }
        }
    }

    /**
     * Registra una compra aprobada con su pago de prueba y un pasaje por cada viaje,
     * usando las relaciones Eloquent (compras(), pagos(), pasajes()).
     */
    private function vender(Viajero $viajero, array $viajes, $fecha, ?Colectivo $colectivo = null): void
    {
        $fecha = CarbonImmutable::parse($fecha);
        $total = collect($viajes)->sum(fn (Viaje $v) => (float) $v->precio);

        $compra = $viajero->compras()->make([
            'referencia' => (string) Str::uuid(),
            'total' => $total,
            'estado' => EstadoCompra::Aprobada,
            'expira_en' => $fecha->addMinutes(15),
            'pagada_en' => $fecha,
        ]);
        $compra->created_at = $compra->updated_at = $fecha;
        $compra->save();

        $pago = $compra->pagos()->make([
            'mp_payment_id' => null,
            'estado' => 'approved',
            'estado_detalle' => 'accredited',
            'monto' => $total,
            'medio_pago' => 'datos de prueba',
            'fecha_aprobacion' => $fecha,
        ]);
        $pago->created_at = $pago->updated_at = $fecha;
        $pago->save();

        foreach ($viajes as $viaje) {
            $bus = $colectivo ?? $viaje->colectivos()->operativos()->first();
            $butaca = $this->butacaLibre($viaje, $bus);

            $pasaje = $compra->pasajes()->make([
                'codigo' => Pasaje::generarCodigo(),
                'viajero_id' => $viajero->id,
                'viaje_id' => $viaje->id,
                'colectivo_id' => $bus->id,
                'nro_butaca' => $butaca,
                'precio' => $viaje->precio,
            ]);
            $pasaje->created_at = $pasaje->updated_at = $fecha;
            $pasaje->save();

            $this->pasajerosPorViaje[$viaje->id][$viajero->id] = true;
        }
    }

    private function butacaLibre(Viaje $viaje, Colectivo $colectivo): int
    {
        $libres = collect(range(1, $colectivo->capacidad))
            ->reject(fn (int $n) => isset($this->vendidas["{$viaje->id}-{$colectivo->id}-{$n}"]));
        $butaca = $libres->random();
        $this->vendidas["{$viaje->id}-{$colectivo->id}-{$butaca}"] = true;

        return $butaca;
    }

    /** Pasajeros ficticios que todavía no viajan en ese viaje (excluye las cuentas de prueba). */
    private function compradoresAleatorios(Viaje $viaje, int $cantidad): Collection
    {
        static $pool = null;
        $pool ??= Viajero::whereNotIn('dni', ['35123456', '40234567', '28345678'])->get();

        $yaViajan = array_keys($this->pasajerosPorViaje[$viaje->id] ?? []);
        $disponibles = $pool->whereNotIn('id', $yaViajan);

        return $disponibles->shuffle()->take(min($cantidad, $disponibles->count()));
    }

    private function viajeDeRuta(string $origen, string $destino, bool $pasado): Viaje
    {
        $query = Viaje::whereHas('origen', fn ($q) => $q->where('nombre', $origen))
            ->whereHas('destino', fn ($q) => $q->where('nombre', $destino))
            ->whereDoesntHave('colectivos', fn ($q) => $q->whereIn('numero_interno', [105, 106]));

        // El realizado más antiguo deja margen para encontrar un viaje de vuelta posterior.
        return ($pasado ? $query->realizados() : $query->programados())
            ->orderBy('salida')->firstOrFail();
    }
}
