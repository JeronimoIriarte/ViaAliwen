<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * COLECTIVO. numero_interno es el "número de colectivo" que figura en el pasaje.
     */
    public function up(): void
    {
        Schema::create('colectivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero_interno')->unique();
            $table->string('patente', 10)->unique();
            $table->unsignedSmallInteger('capacidad');
            $table->enum('estado', ['operativo', 'baja'])->default('operativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colectivos');
    }
};
