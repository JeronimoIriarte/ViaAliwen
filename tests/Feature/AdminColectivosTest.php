<?php

namespace Tests\Feature;

use App\Models\Colectivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class AdminColectivosTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_merge(['numero_interno' => 201, 'patente' => 'ab 123 cd', 'capacidad' => 30, 'estado' => 'operativo'], $cambios);
    }

    public function test_al_crear_un_colectivo_se_generan_sus_butacas(): void
    {
        $this->actingAs($this->crearAdmin())->post('/admin/colectivos', $this->datos())->assertRedirect();

        $colectivo = Colectivo::where('numero_interno', 201)->firstOrFail();
        $this->assertSame('AB123CD', $colectivo->patente); // normalizada
        $this->assertSame(range(1, 30), $colectivo->butacas->pluck('numero')->all());
    }

    public function test_valida_patente_capacidad_y_estado(): void
    {
        $this->actingAs($this->crearAdmin())
            ->post('/admin/colectivos', $this->datos(['patente' => '12-ABC', 'capacidad' => 5, 'estado' => 'roto']))
            ->assertSessionHasErrors(['patente', 'capacidad', 'estado']);
    }

    public function test_aumentar_la_capacidad_agrega_butacas(): void
    {
        $colectivo = $this->crearColectivo(20);

        $this->actingAs($this->crearAdmin())
            ->put("/admin/colectivos/{$colectivo->id}", $this->datos(['numero_interno' => $colectivo->numero_interno, 'patente' => $colectivo->patente, 'capacidad' => 24]))
            ->assertRedirect(route('admin.colectivos.show', $colectivo));

        $this->assertSame(24, $colectivo->butacas()->count());
    }

    public function test_no_se_reduce_la_capacidad_si_una_butaca_quitada_tiene_pasaje(): void
    {
        $colectivo = $this->crearColectivo(20);
        $this->venderPasaje($this->crearViaje(colectivos: [$colectivo]), butaca: 18);
        $datos = $this->datos(['numero_interno' => $colectivo->numero_interno, 'patente' => $colectivo->patente]);

        $this->actingAs($this->crearAdmin())
            ->put("/admin/colectivos/{$colectivo->id}", [...$datos, 'capacidad' => 15])
            ->assertSessionHasErrors('capacidad');
        $this->assertSame(20, $colectivo->fresh()->capacidad);

        // Reducir sin afectar la butaca 18 sí se puede.
        $this->actingAs($this->crearAdmin())->put("/admin/colectivos/{$colectivo->id}", [...$datos, 'capacidad' => 18]);
        $this->assertSame(18, $colectivo->butacas()->count());
    }

    public function test_un_colectivo_con_viajes_no_se_elimina_pero_se_puede_dar_de_baja(): void
    {
        $admin = $this->crearAdmin();
        $colectivo = $this->crearColectivo();
        $this->crearViaje(colectivos: [$colectivo]);

        $this->actingAs($admin)->delete("/admin/colectivos/{$colectivo->id}")->assertSessionHasErrors('colectivo');
        $this->assertModelExists($colectivo);

        $this->actingAs($admin)->put("/admin/colectivos/{$colectivo->id}", $this->datos([
            'numero_interno' => $colectivo->numero_interno, 'patente' => $colectivo->patente, 'capacidad' => 10, 'estado' => 'baja',
        ]));
        $this->assertFalse($colectivo->fresh()->esOperativo());
    }

    public function test_un_colectivo_sin_viajes_se_elimina_con_sus_butacas(): void
    {
        $colectivo = $this->crearColectivo();

        $this->actingAs($this->crearAdmin())->delete("/admin/colectivos/{$colectivo->id}")->assertRedirect(route('admin.colectivos.index'));

        $this->assertModelMissing($colectivo);
        $this->assertDatabaseMissing('butacas', ['colectivo_id' => $colectivo->id]);
    }
}
