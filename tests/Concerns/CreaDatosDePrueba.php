<?php

namespace Tests\Concerns;

use App\Enums\EstadoColectivo;
use App\Enums\EstadoCompra;
use App\Models\Colectivo;
use App\Models\Compra;
use App\Models\Destino;
use App\Models\Pasaje;
use App\Models\User;
use App\Models\Viaje;
use App\Models\Viajero;
use App\Services\ColectivoService;
use Illuminate\Support\Str;

/**
 * Atajos para armar los datos de cada prueba.
 */
trait CreaDatosDePrueba
{
    private int $contadorColectivos = 100;

    protected function crearAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function crearDestino(string $nombre): Destino
    {
        return Destino::firstOrCreate(['nombre' => $nombre], ['descripcion' => "Descripción de prueba de {$nombre}."]);
    }

    protected function crearColectivo(int $capacidad = 10, EstadoColectivo $estado = EstadoColectivo::Operativo): Colectivo
    {
        $numero = ++$this->contadorColectivos;

        return app(ColectivoService::class)->crear([
            'numero_interno' => $numero,
            'patente' => 'AB'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT).'CD',
            'capacidad' => $capacidad,
            'estado' => $estado,
        ]);
    }

    /** Crea un viaje (por defecto en 3 días, 6 horas de duración) con los colectivos indicados. */
    protected function crearViaje(?User $creador = null, array $colectivos = [], int $enDias = 3, int $horas = 6): Viaje
    {
        $creador ??= $this->crearAdmin();
        $origen = Destino::firstOrCreate(['nombre' => 'Neuquén Capital'], ['descripcion' => 'Capital provincial de prueba.']);
        $destino = Destino::firstOrCreate(['nombre' => 'Villa Traful'], ['descripcion' => 'Aldea de montaña de prueba.']);
        $salida = now()->addDays($enDias)->startOfHour();

        $viaje = $creador->viajesCargados()->create([
            'origen_id' => $origen->id,
            'destino_id' => $destino->id,
            'precio' => 39500,
            'salida' => $salida,
            'llegada_estimada' => $salida->copy()->addHours($horas),
        ]);

        $viaje->colectivos()->attach(collect($colectivos ?: [$this->crearColectivo()])->pluck('id'));

        return $viaje;
    }

    protected function venderPasaje(Viaje $viaje, ?Viajero $viajero = null, ?Colectivo $colectivo = null, int $butaca = 1): Pasaje
    {
        $viajero ??= Viajero::factory()->create();
        $colectivo ??= $viaje->colectivos()->first();

        /** @var Compra $compra */
        $compra = $viajero->compras()->create([
            'referencia' => (string) Str::uuid(),
            'total' => $viaje->precio,
            'estado' => EstadoCompra::Aprobada,
            'expira_en' => now()->addMinutes(15),
            'pagada_en' => now(),
        ]);

        return $compra->pasajes()->create([
            'codigo' => Pasaje::generarCodigo(),
            'viajero_id' => $viajero->id,
            'viaje_id' => $viaje->id,
            'colectivo_id' => $colectivo->id,
            'nro_butaca' => $butaca,
            'precio' => $viaje->precio,
        ]);
    }

    /** Reserva una butaca con una compra pendiente; $vencida = true simula una reserva cuyo plazo ya pasó. */
    protected function reservarButaca(Viaje $viaje, Colectivo $colectivo, int $butaca, bool $vencida = false): Compra
    {
        $compra = Viajero::factory()->create()->compras()->create([
            'referencia' => (string) Str::uuid(),
            'total' => $viaje->precio,
            'estado' => EstadoCompra::Pendiente,
            'expira_en' => $vencida ? now()->subMinute() : now()->addMinutes(15),
        ]);

        $compra->reservas()->create([
            'viaje_id' => $viaje->id,
            'colectivo_id' => $colectivo->id,
            'nro_butaca' => $butaca,
            'precio' => $viaje->precio,
        ]);

        return $compra;
    }
}
