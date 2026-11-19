<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use App\Services\EstadisticasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Estadísticas del panel administrativo.
 */
class EstadisticasTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    public function test_el_resumen_distingue_programados_realizados_y_pasajes(): void
    {
        $programado = $this->crearViaje(enDias: 3);
        $realizadoConVentas = $this->crearViaje(enDias: -5);
        $this->crearViaje(enDias: -8); // realizado sin pasajeros: cuenta igual como realizado
        $this->venderPasaje($programado, butaca: 1);
        $this->venderPasaje($realizadoConVentas, butaca: 1);
        $this->venderPasaje($realizadoConVentas, butaca: 2);
        $this->crearColectivo(estado: EstadoColectivo::Baja);

        $resumen = app(EstadisticasService::class)->resumen();

        $this->assertSame(3, $resumen['viajes']);
        $this->assertSame(1, $resumen['programados']);
        $this->assertSame(2, $resumen['realizados']);
        $this->assertSame(1, $resumen['realizados_sin_pasajeros']);
        $this->assertSame(3, $resumen['pasajes']);
        $this->assertSame(118500.0, $resumen['recaudacion']);
        $this->assertSame(3, $resumen['colectivos_operativos']);
        $this->assertSame(1, $resumen['colectivos_baja']);
    }

    public function test_ventas_por_destino_y_por_origen(): void
    {
        $haciaTraful = $this->crearViaje();
        $desdeZapala = $this->crearViaje(enDias: 5);
        $desdeZapala->update([
            'origen_id' => $this->crearDestino('Zapala')->id,
            'destino_id' => $this->crearDestino('Caviahue')->id,
        ]);
        $this->venderPasaje($haciaTraful, butaca: 1);
        $this->venderPasaje($haciaTraful, butaca: 2);
        $this->venderPasaje($desdeZapala, butaca: 1);

        $servicio = app(EstadisticasService::class);
        $porDestino = $servicio->ventasPorDestino()->keyBy('nombre');
        $porOrigen = $servicio->ventasPorOrigen()->keyBy('nombre');

        $this->assertSame(2, $porDestino['Villa Traful']['pasajes']);
        $this->assertSame(79000.0, $porDestino['Villa Traful']['recaudacion']);
        $this->assertSame(1, $porDestino['Caviahue']['pasajes']);
        $this->assertSame(2, $porOrigen['Neuquén Capital']['pasajes']);
        $this->assertSame(1, $porOrigen['Zapala']['pasajes']);
    }

    public function test_pasajes_por_viaje_con_ocupacion(): void
    {
        $viaje = $this->crearViaje(colectivos: [$this->crearColectivo(10), $this->crearColectivo(10)]);
        foreach ([1, 2, 3, 4, 5] as $butaca) {
            $this->venderPasaje($viaje, butaca: $butaca);
        }

        $fila = app(EstadisticasService::class)->pasajesPorViaje()->first();

        $this->assertSame($viaje->id, $fila['viaje']->id);
        $this->assertSame(5, $fila['pasajes']);
        $this->assertSame(20, $fila['capacidad']);
        $this->assertSame(25, $fila['ocupacion']);
    }

    public function test_el_panel_muestra_las_estadisticas(): void
    {
        $viaje = $this->crearViaje();
        $this->venderPasaje($viaje);

        $this->actingAs($this->crearAdmin())->get('/admin')
            ->assertOk()
            ->assertSee('Ventas por destino')
            ->assertSee('Ventas según el origen del viaje')
            ->assertSee('Viajes con más pasajes vendidos')
            ->assertSee('Villa Traful');
    }
}
