<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Búsqueda dinámica (AJAX): GET /viajes/buscar responde JSON.
 */
class BusquedaViajesTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    public function test_responde_json_con_los_datos_de_cada_viaje(): void
    {
        $viaje = $this->crearViaje();

        $this->getJson('/viajes/buscar')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonStructure(['total', 'limite', 'viajes' => [['id', 'url', 'origen', 'destino', 'dia', 'dia_texto', 'salida', 'llegada', 'duracion', 'precio', 'precio_texto', 'libres']]])
            ->assertJsonPath('viajes.0.id', $viaje->id)
            ->assertJsonPath('viajes.0.destino', 'Villa Traful')
            ->assertJsonPath('viajes.0.libres', 10);
    }

    public function test_busca_por_texto_en_destino_u_origen(): void
    {
        $haciaTraful = $this->crearViaje();
        $otro = $this->crearViaje(enDias: 5);
        $otro->update(['destino_id' => $this->crearDestino('Caviahue')->id]);

        $this->getJson('/viajes/buscar?q=trAF')->assertJsonPath('total', 1)->assertJsonPath('viajes.0.id', $haciaTraful->id);
        $this->getJson('/viajes/buscar?q=cavia')->assertJsonPath('total', 1)->assertJsonPath('viajes.0.id', $otro->id);
        $this->getJson('/viajes/buscar?q=Neuqu')->assertJsonPath('total', 2); // ambos salen desde Neuquén Capital
    }

    public function test_filtra_por_destino_origen_y_fecha(): void
    {
        $viaje = $this->crearViaje(enDias: 3);
        $this->crearViaje(enDias: 7);

        $this->getJson('/viajes/buscar?fecha='.$viaje->salida->format('Y-m-d'))
            ->assertJsonPath('total', 1)->assertJsonPath('viajes.0.id', $viaje->id);
        $this->getJson("/viajes/buscar?destino_id={$viaje->destino_id}&origen_id={$viaje->origen_id}")
            ->assertJsonPath('total', 2);
        $this->getJson("/viajes/buscar?origen_id={$viaje->destino_id}")->assertJsonPath('total', 0);
    }

    public function test_solo_devuelve_viajes_disponibles(): void
    {
        $this->crearViaje(colectivos: [$this->crearColectivo(estado: EstadoColectivo::Baja)]);
        $this->crearViaje(enDias: -1);
        $colectivo = $this->crearColectivo(10);
        $completo = $this->crearViaje(colectivos: [$colectivo], enDias: 4);
        foreach (range(1, 10) as $butaca) {
            $this->venderPasaje($completo, colectivo: $colectivo, butaca: $butaca);
        }

        $this->getJson('/viajes/buscar')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('viajes', []);
    }

    public function test_valida_los_parametros_y_responde_422(): void
    {
        $this->getJson('/viajes/buscar?fecha=2000-01-01')->assertStatus(422)->assertJsonValidationErrors('fecha');
        $this->getJson('/viajes/buscar?destino_id=999')->assertStatus(422)->assertJsonValidationErrors('destino_id');
        $this->getJson('/viajes/buscar?q='.str_repeat('a', 61))->assertStatus(422)->assertJsonValidationErrors('q');
    }

    public function test_la_pagina_de_viajes_trae_la_barra_de_busqueda_dinamica(): void
    {
        $this->get('/viajes')
            ->assertOk()
            ->assertSee('data-buscar-url="'.route('viajes.buscar').'"', false)
            ->assertSee('id="plantilla-viaje"', false);
    }
}
