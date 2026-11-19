<?php

namespace Tests\Feature;

use App\Enums\EstadoColectivo;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class AdminViajesTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    private function datosDeViaje(array $cambios = []): array
    {
        $salida = now()->addDays(5)->setTime(8, 0);

        return array_merge([
            'origen_id' => $this->crearDestino('Neuquén Capital')->id,
            'destino_id' => $this->crearDestino('San Martín de los Andes')->id,
            'precio' => '38500',
            'salida' => $salida->format('Y-m-d\TH:i'),
            'llegada_estimada' => $salida->copy()->addHours(6)->format('Y-m-d\TH:i'),
        ], $cambios);
    }

    public function test_el_admin_crea_un_viaje_y_le_asocia_colectivos(): void
    {
        $admin = $this->crearAdmin();
        [$c1, $c2] = [$this->crearColectivo(), $this->crearColectivo()];

        $this->actingAs($admin)
            ->post('/admin/viajes', $this->datosDeViaje(['colectivos' => [$c1->id, $c2->id]]))
            ->assertRedirect();

        $viaje = Viaje::firstOrFail();
        $this->assertSame($admin->id, $viaje->user_id);
        $this->assertEqualsCanonicalizing([$c1->id, $c2->id], $viaje->colectivos->pluck('id')->all());
        $this->assertSame('14:00', $viaje->llegada_estimada->format('H:i')); // la llegada es la que se cargó
    }

    public function test_valida_origen_destino_fechas_precio_y_colectivos(): void
    {
        $destino = $this->crearDestino('Caviahue');

        $this->actingAs($this->crearAdmin())->post('/admin/viajes', [
            'origen_id' => $destino->id,
            'destino_id' => $destino->id,                                   // igual al origen
            'precio' => '-10',                                              // negativo
            'salida' => now()->subDay()->format('Y-m-d\TH:i'),             // en el pasado
            'llegada_estimada' => now()->subDays(2)->format('Y-m-d\TH:i'), // antes de la salida
            'colectivos' => [],
        ])->assertSessionHasErrors(['origen_id', 'precio', 'salida', 'llegada_estimada', 'colectivos']);

        $this->assertDatabaseCount('viajes', 0);
    }

    public function test_no_acepta_colectivos_dados_de_baja_ni_superpuestos(): void
    {
        $admin = $this->crearAdmin();
        $baja = $this->crearColectivo(estado: EstadoColectivo::Baja);
        $ocupado = $this->crearColectivo();
        $this->crearViaje(colectivos: [$ocupado], enDias: 5); // mismo día, se superpone con el horario nuevo

        $datos = $this->datosDeViaje(['salida' => now()->addDays(5)->startOfHour()->format('Y-m-d\TH:i'),
            'llegada_estimada' => now()->addDays(5)->startOfHour()->addHours(3)->format('Y-m-d\TH:i')]);

        $this->actingAs($admin)->post('/admin/viajes', [...$datos, 'colectivos' => [$baja->id]])->assertSessionHasErrors('colectivos');
        $this->actingAs($admin)->post('/admin/viajes', [...$datos, 'colectivos' => [$ocupado->id]])->assertSessionHasErrors('colectivos');
    }

    public function test_otro_administrador_no_puede_editar_ni_eliminar_el_viaje(): void
    {
        $viaje = $this->crearViaje($this->crearAdmin());
        $otro = $this->crearAdmin();

        $this->actingAs($otro)->get("/admin/viajes/{$viaje->id}/edit")->assertForbidden();
        $this->actingAs($otro)->put("/admin/viajes/{$viaje->id}", ['precio' => 1])->assertForbidden();
        $this->actingAs($otro)->delete("/admin/viajes/{$viaje->id}")->assertForbidden();
        $this->assertModelExists($viaje);
    }

    public function test_con_pasajes_vendidos_solo_se_cambia_el_precio(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);
        $pasaje = $this->venderPasaje($viaje);
        $salidaOriginal = $viaje->salida->toDateTimeString();

        $this->actingAs($admin)->put("/admin/viajes/{$viaje->id}", [
            'precio' => '45000',
            'salida' => now()->addDays(20)->format('Y-m-d\TH:i'), // se ignora
            'destino_id' => $viaje->origen_id,                    // se ignora
        ])->assertRedirect(route('admin.viajes.show', $viaje));

        $viaje->refresh();
        $this->assertSame('45000.00', $viaje->precio);
        $this->assertSame($salidaOriginal, $viaje->salida->toDateTimeString());
        $this->assertSame('39500.00', $pasaje->fresh()->precio); // el pasaje conserva lo que se pagó
    }

    public function test_asociar_y_desasociar_colectivos(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);
        $nuevo = $this->crearColectivo(20);

        $this->actingAs($admin)->post("/admin/viajes/{$viaje->id}/colectivos", ['colectivo_id' => $nuevo->id])
            ->assertRedirect(route('admin.viajes.show', $viaje));
        $this->assertTrue($viaje->colectivos()->whereKey($nuevo->id)->exists());

        // No se puede asignar dos veces.
        $this->actingAs($admin)->post("/admin/viajes/{$viaje->id}/colectivos", ['colectivo_id' => $nuevo->id])
            ->assertSessionHasErrors('colectivo_id');

        $this->actingAs($admin)->delete("/admin/viajes/{$viaje->id}/colectivos/{$nuevo->id}")->assertRedirect();
        $this->assertFalse($viaje->colectivos()->whereKey($nuevo->id)->exists());
    }

    public function test_no_se_desasocia_un_colectivo_con_pasajes_vendidos(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);
        $colectivo = $viaje->colectivos()->first();
        $this->venderPasaje($viaje, colectivo: $colectivo);

        $this->actingAs($admin)->delete("/admin/viajes/{$viaje->id}/colectivos/{$colectivo->id}")->assertSessionHas('error');
        $this->assertTrue($viaje->colectivos()->whereKey($colectivo->id)->exists());
    }

    public function test_eliminar_un_viaje_sin_ventas(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);

        $this->actingAs($admin)->delete("/admin/viajes/{$viaje->id}")->assertRedirect(route('admin.viajes.index'));
        $this->assertModelMissing($viaje);
        $this->assertDatabaseMissing('colectivo_viaje', ['viaje_id' => $viaje->id]);
    }

    public function test_las_pantallas_del_panel_se_muestran(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);
        $this->venderPasaje($viaje);

        foreach (['/admin', '/admin/viajes', "/admin/viajes/{$viaje->id}", "/admin/viajes/{$viaje->id}/edit", '/admin/viajes/create',
            '/admin/colectivos', '/admin/colectivos/create', "/admin/colectivos/{$viaje->colectivos->first()->id}",
            '/admin/destinos', '/admin/destinos/create', "/admin/destinos/{$viaje->destino_id}/edit",
            '/admin/viajeros', "/admin/viajeros/{$viaje->pasajes()->first()->viajero_id}"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
