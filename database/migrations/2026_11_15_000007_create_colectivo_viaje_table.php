<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla intermedia de la relación N:M Viaje–Colectivo (equivale a SALE).
     * Sólo guarda el par (viaje, colectivo): origen, destino y horarios ya están en viajes.
     */
    public function up(): void
    {
        Schema::create('colectivo_viaje', function (Blueprint $table) {
            $table->foreignId('viaje_id')->constrained('viajes')->cascadeOnDelete();
            $table->foreignId('colectivo_id')->constrained('colectivos')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['viaje_id', 'colectivo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colectivo_viaje');
    }
};
