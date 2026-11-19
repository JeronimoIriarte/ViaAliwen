<?php

use App\Http\Controllers\Admin\ColectivoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DestinoController as AdminDestinoController;
use App\Http\Controllers\Admin\DestinoImagenController;
use App\Http\Controllers\Admin\ViajeColectivoController;
use App\Http\Controllers\Admin\ViajeController as AdminViajeController;
use App\Http\Controllers\Admin\ViajeroController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PasajeController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ViajeController;
use App\Http\Controllers\WebhookMercadoPagoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas (visitantes, viajeros y administradores)
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

Route::get('/destinos', [DestinoController::class, 'index'])->name('destinos.index');
Route::get('/destinos/{destino}', [DestinoController::class, 'show'])->name('destinos.show')->whereNumber('destino');

Route::get('/viajes', [ViajeController::class, 'index'])->name('viajes.index');
// Búsqueda dinámica: responde JSON (la usa la barra de búsqueda con fetch)
Route::get('/viajes/buscar', [ViajeController::class, 'buscar'])->middleware('throttle:60,1')->name('viajes.buscar');
Route::get('/viajes/{viaje}', [ViajeController::class, 'show'])->name('viajes.show')->whereNumber('viaje');
// Estado de las butacas en JSON: el selector lo consulta con fetch para refrescar el mapa sin recargar.
Route::get('/viajes/{viaje}/butacas', [ViajeController::class, 'butacas'])->name('viajes.butacas')->whereNumber('viaje');

/*
|--------------------------------------------------------------------------
| Mercado Pago
|--------------------------------------------------------------------------
| retorno: a donde vuelve el comprador (no requiere sesión: el pago se verifica con la API).
| webhook: notificaciones de servidor a servidor (excluida de CSRF en bootstrap/app.php).
*/
Route::get('/pagos/retorno', [PagoController::class, 'retorno'])->name('pagos.retorno');
Route::post('/webhooks/mercadopago', WebhookMercadoPagoController::class)->name('webhooks.mercadopago');

/*
|--------------------------------------------------------------------------
| Sólo visitantes (sin sesión iniciada)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // máximo 5 intentos por minuto
        ->name('login.intentar');

    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->name('registro.guardar');
});

Route::post('/salir', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Viajero autenticado
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rol:viajero'])->group(function () {
    Route::get('/mis-datos', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/mis-datos', [PerfilController::class, 'update'])->name('perfil.update');

    // Carrito (guardado en la sesión)
    Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
    Route::post('/carrito', [CarritoController::class, 'store'])->name('carrito.store');
    Route::delete('/carrito/{clave}', [CarritoController::class, 'destroy'])->name('carrito.destroy')
        ->where('clave', '\d+-\d+-\d+');

    // Pago: reserva las butacas y redirige a Mercado Pago (máximo 10 intentos por minuto)
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

    // Historial de compras y acciones sobre una compra pendiente
    Route::get('/mis-compras', [CompraController::class, 'index'])->name('compras.index');
    Route::get('/mis-compras/{compra}', [CompraController::class, 'show'])->name('compras.show');
    // Estado en JSON: la página de una compra pendiente lo consulta con fetch mientras se paga en otra pestaña
    Route::get('/mis-compras/{compra}/estado', [CompraController::class, 'estado'])->middleware('throttle:30,1')->name('compras.estado');
    Route::post('/mis-compras/{compra}/verificar', [PagoController::class, 'verificar'])->middleware('throttle:10,1')->name('compras.verificar');
    Route::post('/mis-compras/{compra}/cancelar', [PagoController::class, 'cancelar'])->name('compras.cancelar');

    // Pasaje en PDF (sólo el titular, controlado por PasajePolicy)
    Route::get('/mis-pasajes/{pasaje}/pdf', [PasajeController::class, 'pdf'])->name('pasajes.pdf');
});

/*
|--------------------------------------------------------------------------
| Administrador-vendedor
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'rol:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Destinos y su galería de fotos
    Route::resource('destinos', AdminDestinoController::class)
        ->except('show')
        ->parameters(['destinos' => 'destino']);
    Route::post('destinos/{destino}/imagenes', [DestinoImagenController::class, 'store'])->name('destinos.imagenes.store');
    Route::delete('destinos/{destino}/imagenes/{imagen}', [DestinoImagenController::class, 'destroy'])->name('destinos.imagenes.destroy');

    // Colectivos (flota) y sus butacas
    Route::resource('colectivos', ColectivoController::class)->parameters(['colectivos' => 'colectivo']);

    // Viajes y asignación de colectivos (relación N:M: attach / detach)
    Route::resource('viajes', AdminViajeController::class)->parameters(['viajes' => 'viaje']);
    Route::post('viajes/{viaje}/colectivos', [ViajeColectivoController::class, 'store'])->name('viajes.colectivos.store');
    Route::delete('viajes/{viaje}/colectivos/{colectivo}', [ViajeColectivoController::class, 'destroy'])->name('viajes.colectivos.destroy');

    // Viajeros (sólo consulta)
    Route::get('viajeros', [ViajeroController::class, 'index'])->name('viajeros.index');
    Route::get('viajeros/{viajero}', [ViajeroController::class, 'show'])->name('viajeros.show');
});

/*
|--------------------------------------------------------------------------
| Cualquier otra URL: página 404 propia, dentro del grupo "web" (con sesión,
| así el encabezado muestra al usuario conectado).
|--------------------------------------------------------------------------
*/
Route::fallback(fn () => abort(404));
