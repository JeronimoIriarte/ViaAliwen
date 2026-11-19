<?php

namespace Tests\Feature;

use App\Models\Destino;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

class AdminDestinosTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    public function test_el_admin_crea_edita_y_lista_destinos(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin)->post('/admin/destinos', [
            'nombre' => 'Villa Pehuenia',
            'descripcion' => 'Aldea de montaña junto a los lagos Aluminé y Moquehue.',
            'destacado' => '1',
        ])->assertRedirect();

        $destino = Destino::where('nombre', 'Villa Pehuenia')->firstOrFail();
        $this->assertTrue($destino->destacado);

        $this->actingAs($admin)->put("/admin/destinos/{$destino->id}", [
            'nombre' => 'Villa Pehuenia',
            'descripcion' => 'Descripción actualizada con más de veinte caracteres.',
        ])->assertRedirect(route('admin.destinos.index'));

        $this->assertFalse($destino->fresh()->destacado);
        $this->actingAs($admin)->get('/admin/destinos')->assertOk()->assertSee('Villa Pehuenia');
    }

    public function test_valida_nombre_unico_y_descripcion(): void
    {
        $this->crearDestino('Caviahue');

        $this->actingAs($this->crearAdmin())
            ->post('/admin/destinos', ['nombre' => 'Caviahue', 'descripcion' => 'corta'])
            ->assertSessionHasErrors(['nombre', 'descripcion']);
    }

    public function test_sube_y_quita_fotos_de_la_galeria(): void
    {
        Storage::fake('public');
        $admin = $this->crearAdmin();
        $destino = $this->crearDestino('Copahue');

        $this->actingAs($admin)->post("/admin/destinos/{$destino->id}/imagenes", [
            'imagenes' => [UploadedFile::fake()->image('termas.jpg', 1200, 800), UploadedFile::fake()->image('volcan.png')],
            'epigrafe' => 'Termas de Copahue',
        ])->assertRedirect(route('admin.destinos.edit', $destino));

        $this->assertCount(2, $destino->imagenes);
        $imagen = $destino->imagenes->first();
        Storage::disk('public')->assertExists($imagen->ruta);

        $this->actingAs($admin)->delete("/admin/destinos/{$destino->id}/imagenes/{$imagen->id}")->assertRedirect();

        Storage::disk('public')->assertMissing($imagen->ruta);
        $this->assertCount(1, $destino->fresh()->imagenes);
    }

    public function test_rechaza_archivos_que_no_son_imagenes(): void
    {
        Storage::fake('public');
        $destino = $this->crearDestino('Zapala');

        $this->actingAs($this->crearAdmin())->post("/admin/destinos/{$destino->id}/imagenes", [
            'imagenes' => [UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('imagenes.0');
    }

    public function test_no_se_puede_quitar_una_foto_desde_otro_destino(): void
    {
        Storage::fake('public');
        $admin = $this->crearAdmin();
        $destino = $this->crearDestino('Aluminé');
        $otro = $this->crearDestino('Chos Malal');
        $imagen = $destino->imagenes()->create(['ruta' => 'destinos/x.jpg', 'orden' => 1]);

        $this->actingAs($admin)->delete("/admin/destinos/{$otro->id}/imagenes/{$imagen->id}")->assertNotFound();
        $this->assertModelExists($imagen);
    }

    public function test_no_se_elimina_un_destino_con_viajes_pero_si_uno_sin_viajes(): void
    {
        $admin = $this->crearAdmin();
        $viaje = $this->crearViaje($admin);
        $sinViajes = $this->crearDestino('Junín de los Andes');

        $this->actingAs($admin)->delete("/admin/destinos/{$viaje->destino_id}")->assertSessionHas('error');
        $this->assertModelExists($viaje->destino);

        $this->actingAs($admin)->delete("/admin/destinos/{$sinViajes->id}")->assertRedirect(route('admin.destinos.index'));
        $this->assertModelMissing($sinViajes);
    }
}
