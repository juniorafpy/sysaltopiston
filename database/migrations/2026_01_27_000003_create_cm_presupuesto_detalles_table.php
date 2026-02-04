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
        // Si la tabla ya existe, no hacer nada
        if (Schema::hasTable('cm_presupuesto_detalles')) {
            return;
        }

        Schema::create('cm_presupuesto_detalles', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('nro_presupuesto');
            $table->unsignedBigInteger('cod_articulo');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('total_iva', 12, 2)->nullable();

            // Foreign keys
            $table->foreign('nro_presupuesto')->references('nro_presupuesto')->on('cm_presupuesto_cabecera')->onDelete('cascade');
            $table->foreign('cod_articulo')->references('cod_articulo')->on('articulos')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_presupuesto_detalles');
    }
};
