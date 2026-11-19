<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Viajero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Protección de rutas por perfil (middleware) y reglas sobre registros concretos (policies).
 */
class AutorizacionTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    // ---------- Middleware: auth + rol ----------

    public function test_un_visitante_es_enviado_al_ingreso_desde_rutas_protegidas(): void
    {
        $this->get('/mis-datos')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/viajes')->assertRedirect(route('login'));
    }

    public function test_un_viajero_no_entra_al_panel_administrativo(): void
    {
        $viajero = Viajero::factory()->create();

        $this->actingAs($viajero->user)->get('/admin')->assertForbidden();
        $this->actingAs($viajero->user)->get('/admin/destinos')->assertForbidden();
        $this->actingAs($viajero->user)->post('/admin/destinos', ['nombre' => 'X'])->assertForbidden();
    }

    public function test_un_administrador_no_entra_a_las_secciones_del_viajero(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/mis-datos')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    // ---------- Perfil: cada viajero sólo modifica sus datos ----------

    public function test_un_viajero_actualiza_sus_datos_pero_no_su_dni(): void
    {
        $viajero = Viajero::factory()->create(['dni' => '30111222']);
        $otro = Viajero::factory()->create(['nombre' => 'Original']);

        $this->actingAs($viajero->user)->put('/mis-datos', [
            'nombre' => 'Nahuel',
            'apellido' => 'Quintriqueo',
            'fecha_nacimiento' => '1990-02-15',
            'dni' => '99999999',       // se ignora: no está en las reglas del Form Request
            'viajero_id' => $otro->id, // se ignora: el controlador usa siempre el viajero autenticado
        ])->assertRedirect(route('perfil.edit'));

        $viajero->refresh();
        $this->assertSame('Nahuel', $viajero->nombre);
        $this->assertSame('30111222', $viajero->dni);
        $this->assertSame('Original', $otro->fresh()->nombre);
    }

    // ---------- Policies ----------

    public function test_solo_el_administrador_que_cargo_el_viaje_puede_modificarlo(): void
    {
        $creador = $this->crearAdmin();
        $viaje = $this->crearViaje($creador);

        $this->assertTrue($creador->can('update', $viaje));
        $this->assertFalse($this->crearAdmin()->can('update', $viaje));
        $this->assertFalse(Viajero::factory()->create()->user->can('update', $viaje));
    }

    public function test_no_se_elimina_un_viaje_con_pasajes_vendidos(): void
    {
        $creador = $this->crearAdmin();
        $viaje = $this->crearViaje($creador);
        $this->assertTrue($creador->can('delete', $viaje));

        $this->venderPasaje($viaje);

        $this->assertFalse($creador->can('delete', $viaje));
    }

    public function test_solo_el_titular_puede_descargar_su_pasaje_y_ver_su_compra(): void
    {
        $titular = Viajero::factory()->create();
        $pasaje = $this->venderPasaje($this->crearViaje(), $titular);
        $otro = Viajero::factory()->create();

        $this->assertTrue($titular->user->can('download', $pasaje));
        $this->assertFalse($otro->user->can('download', $pasaje));
        $this->assertFalse($this->crearAdmin()->can('download', $pasaje));

        $this->assertTrue($titular->user->can('view', $pasaje->compra));
        $this->assertFalse($otro->user->can('view', $pasaje->compra));
    }
}
