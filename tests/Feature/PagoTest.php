<?php

namespace Tests\Feature;

use App\Enums\EstadoCompra;
use App\Exceptions\CompraException;
use App\Models\Compra;
use App\Models\Viajero;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Checkout, reservas y confirmación del pago. Mercado Pago se reemplaza por un doble de prueba
 * (mock): las pruebas no salen a internet y controlamos qué "responde" la API.
 */
class PagoTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    private MockInterface $mercadoPago;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mercadoPago = $this->mock(MercadoPagoService::class);
        $this->mercadoPago->shouldReceive('configurado')->andReturn(true)->byDefault();
        $this->mercadoPago->shouldReceive('crearPreferencia')
            ->andReturn(['id' => 'pref-123', 'url' => 'https://sandbox.mercadopago.com.ar/checkout/pref-123'])
            ->byDefault();
    }

    /** Pago tal como lo devuelve MercadoPagoService::obtenerPago(). */
    private function pago(Compra $compra, string $estado = 'approved', ?float $monto = null, int $id = 111): array
    {
        return [
            'id' => $id, 'estado' => $estado, 'detalle' => $estado === 'approved' ? 'accredited' : 'cc_rejected_other_reason',
            'referencia' => $compra->referencia, 'monto' => $monto ?? (float) $compra->total, 'moneda' => 'ARS',
            'medio' => 'master', 'fecha_aprobacion' => $estado === 'approved' ? now()->toIso8601String() : null,
        ];
    }

    /** Carga el carrito e inicia el pago; devuelve la compra creada. */
    private function iniciarPago(Viajero $viajero, array $butacas): Compra
    {
        foreach ($butacas as [$viaje, $colectivo, $numero]) {
            $this->actingAs($viajero->user)->post('/carrito', [
                'viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => $numero,
            ]);
        }

        $respuesta = $this->actingAs($viajero->user)->post('/checkout');
        $compra = $viajero->compras()->latest('id')->firstOrFail();

        // Queda en la página de la compra (el checkout se abre desde ahí en una pestaña nueva).
        $respuesta->assertRedirect(route('compras.show', $compra));

        return $compra;
    }

    public function test_iniciar_el_pago_reserva_las_butacas_sin_emitir_pasajes(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();

        $compra = $this->iniciarPago($viajero, [[$viaje, $colectivo, 4]]);

        $this->assertSame(EstadoCompra::Pendiente, $compra->estado);
        $this->assertSame('39500.00', $compra->total);
        $this->assertSame('pref-123', $compra->mp_preference_id);
        $this->assertCount(1, $compra->reservas);
        $this->assertCount(0, $compra->pasajes); // nada de pasajes antes del pago
        $this->assertSame([], session('carrito', []));

        // La butaca queda retenida: otra persona no puede agregarla.
        $otro = Viajero::factory()->create();
        $this->actingAs($otro->user)->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => 4])
            ->assertSessionHasErrors('nro_butaca');
    }

    public function test_al_pagar_se_revalida_la_disponibilidad(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();
        $this->actingAs($viajero->user)->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => 4]);

        // Mientras tanto, otra persona reservó la misma butaca.
        $this->reservarButaca($viaje, $colectivo, 4);

        $this->actingAs($viajero->user)->post('/checkout')
            ->assertRedirect(route('carrito.index'))
            ->assertSessionHas('error');
        $this->assertSame(0, $viajero->compras()->count());
    }

    public function test_si_mercado_pago_falla_la_compra_se_cancela_y_libera_las_butacas(): void
    {
        $this->mercadoPago->shouldReceive('crearPreferencia')->andThrow(new CompraException('No pudimos conectarnos con Mercado Pago.'));
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $this->actingAs($viajero->user)->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => $viaje->colectivos->first()->id, 'nro_butaca' => 4]);

        $this->actingAs($viajero->user)->post('/checkout')->assertSessionHas('error', 'No pudimos conectarnos con Mercado Pago.');

        $this->assertSame(EstadoCompra::Cancelada, $viajero->compras()->first()->estado);
    }

    public function test_pago_aprobado_emite_un_pasaje_por_viaje_con_el_precio_pagado(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje1 = $this->crearViaje();
        $viaje2 = $this->crearViaje(enDias: 6);
        $compra = $this->iniciarPago($viajero, [[$viaje1, $viaje1->colectivos->first(), 4], [$viaje2, $viaje2->colectivos->first(), 9]]);

        $viaje1->update(['precio' => 99999]); // un cambio de precio posterior no afecta lo ya pagado
        $this->mercadoPago->shouldReceive('obtenerPago')->with(111)->andReturn($this->pago($compra));

        $this->get('/pagos/retorno?payment_id=111&status=approved&external_reference='.$compra->referencia)
            ->assertRedirect(route('compras.show', $compra));

        $compra->refresh();
        $this->assertSame(EstadoCompra::Aprobada, $compra->estado);
        $this->assertCount(2, $compra->pasajes);
        $this->assertEqualsCanonicalizing([4, 9], $compra->pasajes->pluck('nro_butaca')->all());
        $this->assertSame(['39500.00', '39500.00'], $compra->pasajes->pluck('precio')->all());
        $this->assertSame(1, $compra->pagos()->count());
    }

    public function test_el_status_de_la_url_no_alcanza_para_confirmar(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);

        // La URL dice "approved", pero la API de Mercado Pago informa el pago como rechazado.
        $this->mercadoPago->shouldReceive('obtenerPago')->with(222)->andReturn($this->pago($compra, 'rejected', id: 222));

        $this->get('/pagos/retorno?payment_id=222&status=approved');

        $this->assertSame(EstadoCompra::Pendiente, $compra->fresh()->estado);
        $this->assertCount(0, $compra->pasajes()->get());
        $this->assertSame('rejected', $compra->pagos()->first()->estado); // el intento queda registrado
    }

    public function test_notificaciones_repetidas_no_duplican_pasajes(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);
        $this->mercadoPago->shouldReceive('firmaValida')->andReturn(true);
        $this->mercadoPago->shouldReceive('obtenerPago')->with(111)->andReturn($this->pago($compra));

        foreach (range(1, 3) as $intento) {
            $this->postJson('/webhooks/mercadopago?data.id=111&type=payment', ['type' => 'payment', 'data' => ['id' => '111']])->assertOk();
        }

        $this->assertSame(EstadoCompra::Aprobada, $compra->fresh()->estado);
        $this->assertCount(1, $compra->pasajes()->get());
        $this->assertSame(1, $compra->pagos()->count());
    }

    public function test_un_webhook_con_firma_invalida_se_rechaza(): void
    {
        $this->mercadoPago->shouldReceive('firmaValida')->andReturn(false);
        $this->mercadoPago->shouldNotReceive('obtenerPago');

        $this->postJson('/webhooks/mercadopago?data.id=111&type=payment', [])->assertStatus(401);
    }

    public function test_un_monto_distinto_no_emite_pasajes(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);
        $this->mercadoPago->shouldReceive('obtenerPago')->andReturn($this->pago($compra, monto: 10.0));

        $this->get('/pagos/retorno?payment_id=111');

        $this->assertSame(EstadoCompra::Conflicto, $compra->fresh()->estado);
        $this->assertCount(0, $compra->pasajes()->get());
    }

    public function test_verificar_pago_busca_los_pagos_de_la_compra(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);
        $this->mercadoPago->shouldReceive('buscarPagos')->with($compra->referencia)
            ->andReturn([$this->pago($compra, 'rejected', id: 1), $this->pago($compra, 'approved', id: 2)]);

        $this->actingAs($viajero->user)->post("/mis-compras/{$compra->id}/verificar")->assertRedirect(route('compras.show', $compra));

        $this->assertSame(EstadoCompra::Aprobada, $compra->fresh()->estado);
        $this->assertSame(2, $compra->pagos()->count());
    }

    public function test_un_viajero_no_ve_ni_opera_compras_ajenas(): void
    {
        $duenio = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($duenio, [[$viaje, $viaje->colectivos->first(), 4]]);
        $otro = Viajero::factory()->create();

        $this->actingAs($otro->user)->get("/mis-compras/{$compra->id}")->assertForbidden();
        $this->actingAs($otro->user)->getJson("/mis-compras/{$compra->id}/estado")->assertForbidden();
        $this->actingAs($otro->user)->post("/mis-compras/{$compra->id}/verificar")->assertForbidden();
        $this->actingAs($otro->user)->post("/mis-compras/{$compra->id}/cancelar")->assertForbidden();
    }

    public function test_la_pagina_de_la_compra_abre_el_checkout_en_una_pestana_nueva(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);
        $this->mercadoPago->shouldReceive('buscarPagos')->andReturn([]);

        $this->actingAs($viajero->user)->get("/mis-compras/{$compra->id}")
            ->assertOk()
            ->assertSee('href="https://sandbox.mercadopago.com.ar/checkout/pref-123" target="_blank" rel="noopener"', false)
            ->assertSee('data-estado-url="'.route('compras.estado', $compra).'"', false);
    }

    public function test_el_estado_en_json_detecta_el_pago_aprobado(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $compra = $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);

        $this->mercadoPago->shouldReceive('buscarPagos')->once()->andReturn([]);
        $this->actingAs($viajero->user)->getJson("/mis-compras/{$compra->id}/estado")
            ->assertOk()->assertJson(['estado' => 'pendiente', 'pendiente' => true]);

        // El viajero pagó en la otra pestaña: la próxima consulta ve el pago aprobado y emite el pasaje.
        $this->mercadoPago->shouldReceive('buscarPagos')->andReturn([$this->pago($compra)]);
        $this->actingAs($viajero->user)->getJson("/mis-compras/{$compra->id}/estado")
            ->assertOk()->assertJson(['estado' => 'aprobada', 'pendiente' => false]);

        $this->assertCount(1, $compra->pasajes()->get());
    }

    public function test_cancelar_una_compra_pendiente_libera_las_butacas(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $colectivo = $viaje->colectivos->first();
        $compra = $this->iniciarPago($viajero, [[$viaje, $colectivo, 4]]);
        $this->mercadoPago->shouldReceive('buscarPagos')->andReturn([]); // no hay pagos

        $this->actingAs($viajero->user)->post("/mis-compras/{$compra->id}/cancelar");

        $this->assertSame(EstadoCompra::Cancelada, $compra->fresh()->estado);
        $otro = Viajero::factory()->create();
        $this->actingAs($otro->user)->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => 4])
            ->assertSessionHasNoErrors();
    }

    public function test_una_reserva_vencida_libera_la_butaca(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);
        $compraVencida = $this->reservarButaca($viaje, $colectivo, 4, vencida: true);

        $otro = Viajero::factory()->create();
        $this->actingAs($otro->user)->post('/carrito', ['viaje_id' => $viaje->id, 'colectivo_id' => $colectivo->id, 'nro_butaca' => 4])
            ->assertSessionHasNoErrors();

        $this->artisan('compras:vencer')->assertSuccessful();
        $this->assertSame(EstadoCompra::Vencida, $compraVencida->fresh()->estado);
    }

    public function test_pago_aprobado_tarde_con_la_butaca_ya_vendida_queda_en_conflicto(): void
    {
        $colectivo = $this->crearColectivo(10);
        $viaje = $this->crearViaje(colectivos: [$colectivo]);
        $compra = $this->reservarButaca($viaje, $colectivo, 4, vencida: true);
        $this->venderPasaje($viaje, colectivo: $colectivo, butaca: 4); // mientras tanto otra persona la compró
        $this->mercadoPago->shouldReceive('obtenerPago')->andReturn($this->pago($compra));

        $this->get('/pagos/retorno?payment_id=111');

        $this->assertSame(EstadoCompra::Conflicto, $compra->fresh()->estado);
        $this->assertCount(0, $compra->pasajes()->get());
    }

    public function test_no_se_inicia_otro_pago_con_una_compra_en_curso(): void
    {
        $viajero = Viajero::factory()->create();
        $viaje = $this->crearViaje();
        $this->iniciarPago($viajero, [[$viaje, $viaje->colectivos->first(), 4]]);

        $otroViaje = $this->crearViaje(enDias: 8);
        $this->actingAs($viajero->user)->post('/carrito', ['viaje_id' => $otroViaje->id, 'colectivo_id' => $otroViaje->colectivos->first()->id, 'nro_butaca' => 1]);

        $this->actingAs($viajero->user)->post('/checkout')->assertSessionHas('error');
        $this->assertSame(1, $viajero->compras()->count());
    }
}
