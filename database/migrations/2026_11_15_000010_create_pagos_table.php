<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PAGO: cada intento de pago informado por Mercado Pago (aprobado, rechazado, pendiente...).
     * mp_payment_id único: si Mercado Pago notifica dos veces el mismo pago, se actualiza la misma fila.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->unsignedBigInteger('mp_payment_id')->nullable()->unique();
            $table->string('estado', 30);
            $table->string('estado_detalle', 80)->nullable();
            $table->decimal('monto', 10, 2)->unsigned();
            $table->string('medio_pago', 50)->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
