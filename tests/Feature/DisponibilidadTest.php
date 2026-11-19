<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Reglas de disponibilidad del punto 7 del enunciado.
 */
class DisponibilidadTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    private DisponibilidadService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(DisponibilidadService::class);
    }

    public function test_cuenta_pasajes_vendidos_y_reservas_vigentes_pero_no_las_vencidas(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);

        $this->venderPasaje($viaje, butaca: 1);
        $this->reservarButaca($viaje, $colectivo, 2);                 // pago en curso: ocupa
        $this->reservarButaca($viaje, $colectivo, 3, vencida: true);  // reserva vencida: libera

        $this->assertSame([1, 2], $this->servicio->butacasOcupadas($viaje, $colectivo)->all());
        $this->assertSame(8, $this->servicio->butacasLibres($viaje, $colectivo));
        $this->assertTrue($this->servicio->butacaLibre($viaje, $colectivo, 3));
        $this->assertFalse($this->servicio->butacaLibre($viaje, $colectivo, 2));
        $this->assertFalse($this->servicio->butacaLibre($viaje, $colectivo, 11)); // no existe
    }

    public function test_un_colectivo_dado_de_baja_no_tiene_butacas_libres(): void
    {
        $baja = $this->crearColectivo(10, EstadoColectivo::Baja);
        $viaje = $this->crearViaje(colectivos: [$baja]);

        $this->assertSame(0, $this->servicio->butacasLibres($viaje, $baja));
        $this->assertFalse($this->servicio->estaDisponible($viaje));
    }

    public function test_un_viaje_con_todos_sus_colectivos_completos_no_esta_disponible(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);

        foreach (range(1, 10) as $butaca) {
            $this->venderPasaje($viaje, butaca: $butaca);
        }

        $this->assertFalse($this->servicio->estaDisponible($viaje->fresh()));
        $this->assertFalse($this->servicio->viajesDisponibles()->contains('id', $viaje->id));
    }

    public function test_si_un_colectivo_esta_completo_pero_otro_tiene_lugar_el_viaje_sigue_disponible(): void
    {
        $lleno = $this->crearColectivo(10);
        $conLugar = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$lleno, $conLugar]);

        foreach (range(1, 10) as $butaca) {
            $this->venderPasaje($viaje, colectivo: $lleno, butaca: $butaca);
        }

        $this->assertSame(0, $this->servicio->butacasLibres($viaje, $lleno));
        $this->assertSame(10, $this->servicio->libresTotales($viaje));
        $this->assertTrue($this->servicio->viajesDisponibles()->contains('id', $viaje->id));
    }

    public function test_un_viaje_que_ya_salio_no_esta_disponible(): void
    {
        $viaje = $this->crearViaje(enDias: -1);

        $this->assertFalse($this->servicio->estaDisponible($viaje));
        $this->assertFalse($this->servicio->viajesDisponibles()->contains('id', $viaje->id));
    }
}
