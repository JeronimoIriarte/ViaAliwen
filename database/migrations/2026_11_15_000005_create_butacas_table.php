<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BUTACA: cada colectivo tiene sus propias butacas numeradas 1..capacidad.
     * El asiento se identifica por (colectivo_id, numero), no por el número solo.
     * La disponibilidad NO se guarda acá: depende de cada viaje y se calcula.
     */
    public function up(): void
    {
        Schema::create('butacas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colectivo_id')->constrained('colectivos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');

            $table->unique(['colectivo_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butacas');
    }
};
