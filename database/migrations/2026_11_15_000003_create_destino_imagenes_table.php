<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galería de fotografías de cada destino (1:N). Si se borra el destino, se borran sus fotos.
     */
    public function up(): void
    {
        Schema::create('destino_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destino_id')->constrained('destinos')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('epigrafe', 150)->nullable();
            $table->string('credito', 255)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destino_imagenes');
    }
};
