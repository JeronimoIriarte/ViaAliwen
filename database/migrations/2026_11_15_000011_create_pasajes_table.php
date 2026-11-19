<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PASAJE emitido tras un pago aprobado. Conserva el precio pagado.
     *
     * Integridad garantizada por la propia base de datos:
     *  - UNIQUE(viaje_id, colectivo_id, nro_butaca): una butaca no se vende dos veces en el mismo viaje y colectivo.
     *  - FK (viaje_id, colectivo_id) -> colectivo_viaje: sólo se vende en colectivos asignados a ese viaje.
     *  - FK (colectivo_id, nro_butaca) -> butacas(colectivo_id, numero): la butaca existe en ese colectivo.
     */
    public function up(): void
    {
        Schema::create('pasajes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 12)->unique();
            $table->foreignId('compra_id')->constrained('compras')->restrictOnDelete();
            $table->foreignId('viajero_id')->constrained('viajeros')->restrictOnDelete();
            $table->foreignId('viaje_id')->constrained('viajes')->restrictOnDelete();
            $table->foreignId('colectivo_id')->constrained('colectivos')->restrictOnDelete();
            $table->unsignedSmallInteger('nro_butaca');
            $table->decimal('precio', 10, 2)->unsigned();
            $table->timestamps();

            $table->unique(['viaje_id', 'colectivo_id', 'nro_butaca'], 'pasajes_butaca_unica');

            $table->foreign(['viaje_id', 'colectivo_id'], 'pasajes_viaje_colectivo_fk')
                ->references(['viaje_id', 'colectivo_id'])->on('colectivo_viaje')
                ->restrictOnDelete();

            $table->foreign(['colectivo_id', 'nro_butaca'], 'pasajes_butaca_fk')
                ->references(['colectivo_id', 'numero'])->on('butacas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasajes');
    }
};
