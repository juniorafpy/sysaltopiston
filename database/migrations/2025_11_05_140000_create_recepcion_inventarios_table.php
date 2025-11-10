<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_vehiculo_id')->constrained('recepcion_vehiculos')->onDelete('cascade');

            // Inventario de artículos del vehículo
            $table->boolean('extintor')->default(false);
            $table->boolean('valija')->default(false);
            $table->boolean('rueda_auxilio')->default(false);
            $table->boolean('gato')->default(false);
            $table->boolean('llave_ruedas')->default(false);
            $table->boolean('triangulos_seguridad')->default(false);
            $table->boolean('botiquin')->default(false);
            $table->boolean('manual_vehiculo')->default(false);
            $table->boolean('llave_repuesto')->default(false);
            $table->boolean('radio_estereo')->default(false);

            // Nivel de combustible (en porcentaje o fracción)
            $table->enum('nivel_combustible', ['vacio', '1/4', '2/4', '3/4', 'lleno'])->nullable();

            // Observaciones adicionales
            $table->text('observaciones_inventario')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_inventarios');
    }
};
