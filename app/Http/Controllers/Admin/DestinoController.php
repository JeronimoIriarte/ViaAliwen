<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestinoRequest;
use App\Models\Destino;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * CRUD de destinos (panel administrativo). Las fotos se gestionan en DestinoImagenController.
 */
class DestinoController extends Controller
{
    public function index(): View
    {
        $destinos = Destino::with('portada')
            ->withCount(['imagenes', 'viajes', 'viajesDesde'])
            ->orderBy('nombre')
            ->get();

        return view('admin.destinos.index', compact('destinos'));
    }

    public function create(): View
    {
        return view('admin.destinos.create', ['destino' => new Destino]);
    }

    public function store(DestinoRequest $request): RedirectResponse
    {
        $destino = Destino::create($request->validated());

        return redirect()->route('admin.destinos.edit', $destino)
            ->with('exito', "Destino \"{$destino->nombre}\" creado. Ahora podés cargar sus fotos.");
    }

    public function edit(Destino $destino): View
    {
        $destino->load('imagenes');

        return view('admin.destinos.edit', compact('destino'));
    }

    public function update(DestinoRequest $request, Destino $destino): RedirectResponse
    {
        $destino->update($request->validated());

        return redirect()->route('admin.destinos.index')->with('exito', "Destino \"{$destino->nombre}\" actualizado.");
    }

    public function destroy(Destino $destino): RedirectResponse
    {
        // No se borra un destino que es origen o destino de algún viaje (además lo impide la FK).
        if ($destino->viajes()->exists() || $destino->viajesDesde()->exists()) {
            return back()->with('error', "No se puede eliminar \"{$destino->nombre}\": tiene viajes asociados.");
        }

        // Se borran los archivos de la galería; las filas se borran en cascada con el destino.
        Storage::disk('public')->delete($destino->imagenes()->pluck('ruta')->all());
        $destino->delete();

        return redirect()->route('admin.destinos.index')->with('exito', "Destino \"{$destino->nombre}\" eliminado.");
    }
}
