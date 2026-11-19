<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RESERVA: butaca retenida por una compra mientras su pago está pendiente.
     * No es un pasaje: los pasajes se crean recién cuando el pago queda aprobado.
     * El precio se congela al iniciar el pago (es lo que se cobra en Mercado Pago).
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('viaje_id')->constrained('viajes')->cascadeOnDelete();
            $table->foreignId('colectivo_id')->constrained('colectivos')->cascadeOnDelete();
            $table->unsignedSmallInteger('nro_butaca');
            $table->decimal('precio', 10, 2)->unsigned();

            $table->unique(['compra_id', 'viaje_id', 'colectivo_id', 'nro_butaca']);
            $table->index(['viaje_id', 'colectivo_id', 'nro_butaca']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
