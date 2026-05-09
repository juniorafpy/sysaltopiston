<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recepcion_vehiculo_items_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_vehiculo_id')
                ->constrained('recepcion_vehiculos')
                ->onDelete('cascade');
            $table->unsignedInteger('cod_inventario');
            $table->foreign('cod_inventario')
                ->references('cod_inventario')
                ->on('sm_inventario')
                ->onDelete('cascade');
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['recepcion_vehiculo_id', 'cod_inventario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepcion_vehiculo_items_inventario');
    }
};
