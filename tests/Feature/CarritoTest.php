<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use App\Models\Viajero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Selección de butacas y carrito.
 */
class CarritoTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    private function agregar(Viajero $viajero, $viaje, $colectivo, int $butaca)
    {
        return $this->actingAs($viajero->user)->post('/carrito', [
            'viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => $butaca,
        ]);
    }

    public function test_un_visitante_no_puede_agregar_al_carrito_y_se_le_pide_ingresar(): void
    {
        $viaje = $this->crearViaje();

        $this->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => 1, 'nro_butaca' => 1])->assertRedirect(route('login'));
        $this->get('/carrito')->assertRedirect(route('login'));
        $this->get("/viajes/{$viaje->id}")->assertSee('Ingresá para comprar');
    }

    public function test_un_administrador_no_compra_pasajes(): void
    {
        $this->actingAs($this->crearAdmin())->get('/carrito')->assertForbidden();
    }

    public function test_el_viajero_agrega_butacas_de_varios_viajes_y_ve_el_total(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje1 = $this->crearViaje();
        $viaje2 = $this->crearViaje(enDias: 6);

        $this->agregar($viajero, $viaje1, $viaje1->colectivos->first(), 4)->assertRedirect(route('carrito.index'));
        $this->agregar($viajero, $viaje2, $viaje2->colectivos->first(), 7)->assertRedirect(route('carrito.index'));

        $this->actingAs($viajero->user)->get('/carrito')
            ->assertOk()
            ->assertSee('Butaca 4')
            ->assertSee('Butaca 7')
            ->assertSee(Number::currency(79000), false);
    }

    public function test_no_se_agrega_una_butaca_vendida_reservada_o_inexistente(): void
    {
        $viajero = Viajero::factory()->create();
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);
        $this->venderPasaje($viaje, colectivo: $colectivo, butaca: 1);
        $this->reservarButaca($viaje, $colectivo, 2);

        foreach ([1, 2, 11] as $butaca) {
            $this->from("/viajes/{$viaje->id}");
            $this->agregar($viajero, $viaje, $colectivo, $butaca)->assertSessionHasErrors('nro_butaca');
        }

        $this->assertSame([], session('carrito', []));
    }

    public function test_no_se_vende_en_un_colectivo_dado_de_baja_ni_en_uno_no_asignado(): void
    {
        $viajero = Viajero::factory()->create();
        $baja = $this->crearColectivo(estado: EstadoColectivo::Baja);
        $viaje = $this->crearViaje(colectivos: [$baja]);
        $ajeno = $this->crearColectivo();

        $this->agregar($viajero, $viaje, $baja, 1)->assertSessionHasErrors('nro_butaca');
        $this->agregar($viajero, $viaje, $ajeno, 1)->assertSessionHasErrors('nro_butaca');
    }

    public function test_una_sola_butaca_por_viaje_y_sin_repetir(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();

        $this->agregar($viajero, $viaje, $colectivo, 3);
        $this->agregar($viajero, $viaje, $colectivo, 3)->assertSessionHasErrors('nro_butaca'); // la misma
        $this->agregar($viajero, $viaje, $colectivo, 5)->assertSessionHasErrors('nro_butaca'); // otra del mismo viaje

        $this->assertCount(1, session('carrito'));
    }

    public function test_quitar_un_viaje_del_carrito(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();
        $this->agregar($viajero, $viaje, $colectivo, 3);

        $this->actingAs($viajero->user)->delete("/carrito/{$viaje->id}-{$colectivo->id}-3")->assertRedirect(route('carrito.index'));

        $this->assertSame([], session('carrito'));
    }

    public function test_el_carrito_avisa_si_una_butaca_se_vendio_despues_de_agregarla(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();
        $this->agregar($viajero, $viaje, $colectivo, 3);

        $this->venderPasaje($viaje, colectivo: $colectivo, butaca: 3); // otra persona la compró

        $this->actingAs($viajero->user)->get('/carrito')->assertSee('ya fue vendida o reservada por otra persona');
    }

    public function test_el_estado_de_las_butacas_se_consulta_en_json(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);
        $this->venderPasaje($viaje, colectivo: $colectivo, butaca: 5);
        $this->reservarButaca($viaje, $colectivo, 6);

        $this->getJson("/viajes/{$viaje->id}/butacas")
            ->assertOk()
            ->assertJsonPath('disponible', true)
            ->assertJsonPath('colectivos.0.vendidas', [5])
            ->assertJsonPath('colectivos.0.reservadas', [6])
            ->assertJsonPath('colectivos.0.libres', 8);
    }
}
