<?php

namespace Tests\Feature;

use App\Enums\EstadoCompra;
use App\Models\Viajero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\Concerns\CreaDatosDePrueba;
use Tests\TestCase;

/**
 * Pasajes en PDF (barryvdh/laravel-dompdf): un PDF por pasaje y descargas protegidas.
 */
class PasajePdfTest extends TestCase
{
    use CreaDatosDePrueba;
    use RefreshDatabase;

    public function test_el_titular_descarga_su_pasaje_en_pdf(): void
    {
        $titular = Viajero::factory()->create();
        $pasaje = $this->venderPasaje($this->crearViaje(), $titular, butaca: 7);

        $respuesta = $this->actingAs($titular->user)->get("/mis-pasajes/{$pasaje->id}/pdf");

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');
        $respuesta->assertDownload("pasaje-{$pasaje->codigo}.pdf");
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_cada_pasaje_de_una_compra_tiene_su_propio_pdf(): void
    {
        $titular = Viajero::factory()->create();
        $p1 = $this->venderPasaje($this->crearViaje(), $titular, butaca: 1);
        $p2 = $this->venderPasaje($this->crearViaje(enDias: 6), $titular, butaca: 2);

        $this->actingAs($titular->user)->get("/mis-pasajes/{$p1->id}/pdf")->assertDownload("pasaje-{$p1->codigo}.pdf");
        $this->actingAs($titular->user)->get("/mis-pasajes/{$p2->id}/pdf")->assertDownload("pasaje-{$p2->codigo}.pdf");
    }

    public function test_nadie_mas_puede_descargar_un_pasaje_ajeno(): void
    {
        $pasaje = $this->venderPasaje($this->crearViaje(), Viajero::factory()->create());
        $url = "/mis-pasajes/{$pasaje->id}/pdf";

        $this->get($url)->assertRedirect(route('login'));                                            // visitante
        $this->actingAs(Viajero::factory()->create()->user)->get($url)->assertForbidden();           // otro viajero
        $this->actingAs($this->crearAdmin())->get($url)->assertForbidden();                          // administrador
    }

    public function test_no_se_genera_pdf_de_una_compra_que_no_esta_aprobada(): void
    {
        $titular = Viajero::factory()->create();
        $pasaje = $this->venderPasaje($this->crearViaje(), $titular);
        $pasaje->compra->update(['estado' => EstadoCompra::Conflicto]);

        $this->actingAs($titular->user)->get("/mis-pasajes/{$pasaje->id}/pdf")->assertNotFound();
    }

    public function test_el_pasaje_contiene_todos_los_datos_requeridos(): void
    {
        $titular = Viajero::factory()->create(['nombre' => 'Rocío', 'apellido' => 'Huenchul', 'dni' => '35111222']);
        $pasaje = $this->venderPasaje($this->crearViaje(), $titular, butaca: 9);
        $pasaje->load(['viajero', 'viaje.origen', 'viaje.destino', 'colectivo', 'compra']);
        $viaje = $pasaje->viaje;

        // Se revisa el HTML de la plantilla que DomPDF convierte en PDF.
        $html = view('pdf.pasaje', ['pasaje' => $pasaje, 'pago' => null])->render();

        foreach ([
            'VíaAliwén',                                     // empresa
            $pasaje->codigo,                                 // identificador del pasaje
            'Rocío Huenchul',                                // nombre y apellido
            '35.111.222',                                    // DNI
            $viaje->origen->nombre,                          // origen
            $viaje->destino->nombre,                         // destino
            $viaje->salida->format('d/m/Y'),                 // fecha de salida
            $viaje->salida->format('H:i'),                   // horario de salida
            $viaje->llegada_estimada->format('d/m/Y'),       // fecha estimada de llegada
            $viaje->llegada_estimada->format('H:i'),         // horario estimado de llegada
            (string) $pasaje->colectivo->numero_interno,     // número de colectivo
            'Butaca',
            Number::currency(39500),     // precio abonado
            $pasaje->compra->referencia,                     // referencia de compra
            $pasaje->created_at->format('d/m/Y H:i'),        // fecha de emisión
        ] as $dato) {
            $this->assertStringContainsString($dato, $html, "Falta en el pasaje: {$dato}");
        }
    }

    public function test_mis_compras_ofrece_la_descarga_de_cada_pasaje(): void
    {
        $titular = Viajero::factory()->create();
        $pasaje = $this->venderPasaje($this->crearViaje(), $titular);

        $this->actingAs($titular->user)->get('/mis-compras')->assertSee(route('pasajes.pdf', $pasaje));
        $this->actingAs($titular->user)->get("/mis-compras/{$pasaje->compra_id}")->assertSee(route('pasajes.pdf', $pasaje));
    }
}
