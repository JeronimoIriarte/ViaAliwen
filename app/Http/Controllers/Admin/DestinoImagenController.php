<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestinoImagenRequest;
use App\Models\Destino;
use App\Models\DestinoImagen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Carga y baja de fotos de la galería de un destino.
 */
class DestinoImagenController extends Controller
{
    public function store(DestinoImagenRequest $request, Destino $destino): RedirectResponse
    {
        $orden = (int) $destino->imagenes()->max('orden');

        foreach ($request->file('imagenes') as $archivo) {
            // Se guarda en storage/app/public/destinos/{id}/ con un nombre único generado por Laravel.
            $ruta = $archivo->store("destinos/{$destino->id}", 'public');

            $destino->imagenes()->create([
                'ruta' => $ruta,
                'epigrafe' => $request->validated('epigrafe'),
                'credito' => $request->validated('credito'),
                'orden' => ++$orden,
            ]);
        }

        $cantidad = count($request->file('imagenes'));

        return redirect()->route('admin.destinos.edit', $destino)
            ->with('exito', $cantidad === 1 ? 'Se agregó 1 foto a la galería.' : "Se agregaron {$cantidad} fotos a la galería.");
    }

    public function destroy(Destino $destino, DestinoImagen $imagen): RedirectResponse
    {
        // La foto tiene que pertenecer al destino de la URL.
        abort_unless((int) $imagen->destino_id === $destino->id, 404);

        Storage::disk('public')->delete($imagen->ruta);
        $imagen->delete();

        return redirect()->route('admin.destinos.edit', $destino)->with('exito', 'Foto eliminada de la galería.');
    }
}
