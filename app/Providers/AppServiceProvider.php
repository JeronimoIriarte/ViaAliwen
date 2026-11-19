<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Formato regional: fechas en español y montos en pesos argentinos ($ 38.500,00).
        Carbon::setLocale(config('app.locale'));
        Number::useLocale('es_AR');
        Number::useCurrency('ARS');

        // Uso en las vistas: @pesos($viaje->precio)
        Blade::directive('pesos', fn (string $monto) => "<?php echo e(\\Illuminate\\Support\\Number::currency((float) ({$monto}))); ?>");

        // Los enlaces de paginación usan las clases de Bootstrap.
        Paginator::useBootstrapFive();
    }
}
