<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Registro de viajeros. Los administradores no se registran desde la web (se crean por seeder).
 */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Usuario (credenciales) y viajero (datos personales) se crean juntos o no se crea ninguno.
        $usuario = DB::transaction(function () use ($datos) {
            $usuario = new User([
                'email' => $datos['email'],
                'password' => $datos['password'], // el cast "hashed" la guarda encriptada
            ]);
            $usuario->rol = Rol::Viajero; // el rol nunca se toma del formulario
            $usuario->save();

            // Relación 1:1: crea el viajero con user_id = $usuario->id.
            $usuario->viajero()->create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'dni' => $datos['dni'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
            ]);

            return $usuario;
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('exito', "¡Bienvenido/a a VíaAliwén, {$datos['nombre']}! Tu cuenta quedó creada.");
    }
}
