<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use App\Models\Viajero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registro, inicio y cierre de sesión.
 */
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private function datosDeRegistro(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Rocío',
            'apellido' => 'Huenchul',
            'dni' => '35.111.222',
            'fecha_nacimiento' => '1995-06-20',
            'email' => 'Rocio@Correo.test',
            'password' => 'clave1234',
            'password_confirmation' => 'clave1234',
        ], $cambios);
    }

    public function test_los_formularios_de_ingreso_y_registro_se_muestran(): void
    {
        $this->get('/ingresar')->assertOk()->assertSee('Ingresá a tu cuenta');
        $this->get('/registro')->assertOk()->assertSee('Creá tu cuenta de viajero');
    }

    public function test_un_visitante_se_registra_como_viajero_y_queda_logueado(): void
    {
        $this->post('/registro', $this->datosDeRegistro())
            ->assertRedirect(route('home'))
            ->assertSessionHas('exito');

        $usuario = User::where('email', 'rocio@correo.test')->first(); // email normalizado a minúsculas
        $this->assertNotNull($usuario);
        $this->assertSame(Rol::Viajero, $usuario->rol);
        $this->assertSame('35111222', $usuario->viajero->dni);          // DNI sin puntos
        $this->assertNotSame('clave1234', $usuario->password);           // contraseña hasheada
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_registro_ignora_un_rol_enviado_en_el_formulario(): void
    {
        $this->post('/registro', $this->datosDeRegistro(['rol' => 'admin']));

        $this->assertSame(Rol::Viajero, User::where('email', 'rocio@correo.test')->first()->rol);
    }

    public function test_el_registro_rechaza_dni_y_correo_repetidos(): void
    {
        $existente = Viajero::factory()->create(['dni' => '35111222']);

        $this->post('/registro', $this->datosDeRegistro(['email' => $existente->user->email]))
            ->assertSessionHasErrors(['dni', 'email']);

        $this->assertGuest();
    }

    public function test_el_registro_valida_los_campos(): void
    {
        $this->post('/registro', $this->datosDeRegistro([
            'nombre' => '',
            'dni' => '12',
            'fecha_nacimiento' => now()->addDay()->format('Y-m-d'),
            'email' => 'no-es-un-correo',
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ]))->assertSessionHasErrors(['nombre', 'dni', 'fecha_nacimiento', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_un_viajero_inicia_sesion_y_vuelve_al_inicio(): void
    {
        $viajero = Viajero::factory()->create();

        $this->post('/ingresar', ['email' => $viajero->user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($viajero->user);
    }

    public function test_un_administrador_inicia_sesion_y_va_al_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/ingresar', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_credenciales_incorrectas_no_inician_sesion(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from('/ingresar')
            ->post('/ingresar', ['email' => $admin->email, 'password' => 'incorrecta'])
            ->assertRedirect('/ingresar')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cerrar_sesion(): void
    {
        $viajero = Viajero::factory()->create();

        $this->actingAs($viajero->user)->post('/salir')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_un_usuario_con_sesion_iniciada_no_ve_el_formulario_de_ingreso(): void
    {
        $viajero = Viajero::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($viajero->user)->get('/ingresar')->assertRedirect(route('home'));
        $this->actingAs($admin)->get('/registro')->assertRedirect(route('admin.dashboard'));
    }
}
