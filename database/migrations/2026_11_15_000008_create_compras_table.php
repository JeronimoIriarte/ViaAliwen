<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * COMPRA: una operación de pago. referencia se envía a Mercado Pago como
     * external_reference y permite identificar la compra cuando Mercado Pago informa el pago.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viajero_id')->constrained('viajeros')->restrictOnDelete();
            $table->uuid('referencia')->unique();
            $table->decimal('total', 10, 2)->unsigned();
            $table->enum('estado', ['pendiente', 'aprobada', 'vencida', 'cancelada', 'conflicto'])
                ->default('pendiente');
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_checkout_url', 500)->nullable(); // para retomar el pago mientras la reserva esté vigente
            $table->dateTime('expira_en');
            $table->dateTime('pagada_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'expira_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
