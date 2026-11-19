<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesión.
 */
class AuthController extends Controller
{
    public function mostrarLogin(Request $request): View
    {
        // ?volver=/viajes/12 -> después de ingresar vuelve a esa página (sólo rutas internas, para evitar redirecciones abiertas).
        $volver = (string) $request->query('volver');
        if (str_starts_with($volver, '/') && ! str_starts_with($volver, '//') && ! str_contains($volver, '\\')) {
            $request->session()->put('url.intended', url($volver));
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credenciales = $request->only('email', 'password');

        // Auth::attempt busca el usuario por email y compara la contraseña con el hash guardado.
        if (! Auth::attempt($credenciales, $request->boolean('recordarme'))) {
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->onlyInput('email');
        }

        // Nuevo identificador de sesión: evita la fijación de sesión.
        $request->session()->regenerate();

        $usuario = $request->user();

        // intended(): si el usuario venía de una página protegida, vuelve a ella.
        $destino = $usuario->esAdmin() ? route('admin.dashboard') : route('home');

        return redirect()->intended($destino)
            ->with('exito', "¡Hola, {$usuario->nombreVisible()}! Iniciaste sesión correctamente.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Invalida la sesión y renueva el token CSRF.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('exito', 'Cerraste sesión. ¡Hasta el próximo viaje!');
    }
}
