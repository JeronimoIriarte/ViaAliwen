<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VIAJERO: datos personales del comprador. Relación 1:1 con users
     * (user_id único). El correo electrónico se toma de users para no duplicarlo.
     */
    public function up(): void
    {
        Schema::create('viajeros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('dni', 8)->unique();
            $table->date('fecha_nacimiento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viajeros');
    }
};
