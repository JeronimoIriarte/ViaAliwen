<?php

use App\Services\CheckoutService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| php artisan compras:vencer
| Marca como vencidas las compras pendientes cuyo plazo de reserva terminó.
| La aplicación también lo hace sola al iniciar un pago o al abrir "Mis compras";
| el comando sirve para correrlo a mano o con el programador de tareas (php artisan schedule:work).
*/
Artisan::command('compras:vencer', function (CheckoutService $checkout) {
    $cantidad = $checkout->marcarVencidas();
    $this->info("Compras marcadas como vencidas: {$cantidad}");
})->purpose('Marca como vencidas las compras con la reserva de butacas expirada');

Schedule::command('compras:vencer')->everyFiveMinutes();
