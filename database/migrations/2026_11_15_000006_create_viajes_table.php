<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VIAJE. Origen y destino son FK a destinos. La llegada estimada se carga
     * a mano al crear el viaje (no se calcula). user_id es el admin que lo cargó.
     */
    public function up(): void
    {
        Schema::create('viajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destino_id')->constrained('destinos')->restrictOnDelete();
            $table->foreignId('origen_id')->constrained('destinos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('precio', 10, 2)->unsigned();
            $table->dateTime('salida')->index();
            $table->dateTime('llegada_estimada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viajes');
    }
};
