<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use App\Models\Viajero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Secciones públicas: un visitante consulta destinos, galerías, viajes, horarios y precios.
 */
class PaginasPublicasTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    public function test_el_inicio_muestra_destacados_y_proximos_viajes(): void
    {
        $viaje = $this->crearViaje();
        $viaje->destino->update(['destacado' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Destinos destacados')
            ->assertSee('Villa Traful')
            ->assertSee(Number::currency(39500), false);
    }

    public function test_listado_y_detalle_de_destinos_con_galeria(): void
    {
        $destino = $this->crearDestino('Caviahue');
        $destino->imagenes()->create(['ruta' => 'destinos/caviahue/01.jpg', 'epigrafe' => 'El lago Caviahue', 'credito' => 'Autor · CC0', 'orden' => 1]);
        $destino->imagenes()->create(['ruta' => 'destinos/caviahue/02.jpg', 'epigrafe' => 'Araucarias', 'orden' => 2]);

        $this->get('/destinos')->assertOk()->assertSee('Caviahue');

        $this->get("/destinos/{$destino->id}")
            ->assertOk()
            ->assertSee('El lago Caviahue')
            ->assertSee('Araucarias')
            ->assertSee('data-bs-slide-to="1"', false) // miniatura que lleva a la segunda foto
            ->assertSee('Autor · CC0');
    }

    public function test_el_listado_de_viajes_solo_muestra_los_disponibles(): void
    {
        $disponible = $this->crearViaje();
        $completo = $this->crearViaje(colectivos: [$colectivoChico = $this->crearColectivo(10)], enDias: 4);
        foreach (range(1, 10) as $butaca) {
            $this->venderPasaje($completo, colectivo: $colectivoChico, butaca: $butaca);
        }
        $deBaja = $this->crearViaje(colectivos: [$this->crearColectivo(estado: EstadoColectivo::Baja)], enDias: 5);
        $pasado = $this->crearViaje(enDias: -2);

        $respuesta = $this->get('/viajes')->assertOk();

        $respuesta->assertSee(route('viajes.show', $disponible));
        foreach ([$completo, $deBaja, $pasado] as $noDisponible) {
            $respuesta->assertDontSee(route('viajes.show', $noDisponible));
        }
    }

    public function test_los_filtros_por_destino_y_fecha_funcionan_y_se_validan(): void
    {
        $viaje = $this->crearViaje(enDias: 3);
        $otroDestino = $this->crearDestino('Zapala');

        $this->get('/viajes?destino_id='.$viaje->destino_id.'&fecha='.$viaje->salida->format('Y-m-d'))
            ->assertOk()->assertSee(route('viajes.show', $viaje));

        $this->get('/viajes?destino_id='.$otroDestino->id)
            ->assertOk()->assertSee('No encontramos viajes')->assertDontSee(route('viajes.show', $viaje));

        $this->get('/viajes?fecha=2000-01-01')->assertSessionHasErrors('fecha');
    }

    public function test_el_detalle_muestra_horarios_precio_y_colectivos(): void
    {
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();

        $this->get("/viajes/{$viaje->id}")
            ->assertOk()
            ->assertSee(Number::currency(39500), false)
            ->assertSee("Colectivo N.º {$colectivo->numero_interno}")
            ->assertSee($viaje->llegada_estimada->format('H:i'))
            ->assertSee('Ingresá para comprar'); // al visitante se le pide iniciar sesión
    }

    public function test_un_viaje_completo_muestra_que_no_hay_lugar(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);
        foreach (range(1, 10) as $butaca) {
            $this->venderPasaje($viaje, butaca: $butaca);
        }

        $this->get("/viajes/{$viaje->id}")->assertOk()->assertSee('No quedan butacas disponibles');
    }

    public function test_paginas_de_error_con_la_estetica_del_sitio(): void
    {
        $this->get('/viajes/999999')->assertNotFound()->assertSee('Nos desviamos del camino');

        $viajero = Viajero::factory()->create();
        $this->actingAs($viajero->user)->get('/admin')->assertForbidden()->assertSee('No tenés permiso para acceder a esta sección.');
    }
}
