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
        Schema::create('orden_compra_detalle', function (Blueprint $table) {
            $table->id('id_detalle');
            
            // Relación con cabecera
            $table->unsignedBigInteger('nro_orden_compra');
            $table->foreign('nro_orden_compra')
                  ->references('nro_orden_compra')
                  ->on('orden_compra_cabecera')
                  ->onDelete('cascade');
            
            // Relación con artículo
            $table->unsignedBigInteger('cod_articulo');
            $table->foreign('cod_articulo')
                  ->references('cod_articulo')
                  ->on('articulos')
                  ->onDelete('restrict');
            
            // Datos del detalle
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio', 15, 2);
            $table->decimal('total', 15, 2);
            $table->decimal('total_iva', 15, 2)->default(0);
            
            // Índices
            $table->index('nro_orden_compra');
            $table->index('cod_articulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalle');
    }
};
