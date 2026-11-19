<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Datos personales del viajero autenticado.
 * No recibe ningún id por la URL: siempre trabaja con auth()->user()->viajero,
 * por eso es imposible ver o modificar los datos de otro viajero.
 */
class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', [
            'usuario' => $request->user(),
            'viajero' => $request->user()->viajero,
        ]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        $request->user()->viajero->update($request->validated());

        return redirect()->route('perfil.edit')->with('exito', 'Tus datos se actualizaron correctamente.');
    }
}
