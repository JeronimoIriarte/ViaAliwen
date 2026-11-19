<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para proteger rutas por perfil: ->middleware('rol:admin') o ->middleware('rol:viajero').
        $middleware->alias([
            'rol' => EnsureUserHasRole::class,
        ]);

        // Un visitante que entra a una ruta protegida va al formulario de ingreso.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Un usuario que ya inició sesión y entra a /ingresar o /registro va a su página de inicio.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->esAdmin()
            ? route('admin.dashboard')
            : route('home'));

        // Las notificaciones de Mercado Pago llegan desde su servidor, sin token CSRF.
        // Su autenticidad se controla con la firma x-signature y consultando el pago a la API.
        $middleware->validateCsrfTokens(except: ['webhooks/mercadopago']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
