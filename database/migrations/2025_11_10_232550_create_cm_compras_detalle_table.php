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
        Schema::create('cm_compras_detalle', function (Blueprint $table) {
            $table->id('id_compra_detalle');

            // Relación con cabecera de compra
            $table->unsignedBigInteger('id_compra_cabecera');
            $table->foreign('id_compra_cabecera')
                  ->references('id_compra_cabecera')
                  ->on('cm_compras_cabecera')
                  ->onDelete('cascade');

            // Relación con artículo
            $table->unsignedBigInteger('cod_articulo');
            $table->foreign('cod_articulo')
                  ->references('cod_articulo')
                  ->on('articulos')
                  ->onDelete('restrict');

            // Datos del detalle
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('porcentaje_iva', 5, 2)->default(10.00);
            $table->decimal('monto_total_linea', 15, 2);

            // Índices
            $table->index('id_compra_cabecera');
            $table->index('cod_articulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_compras_detalle');
    }
};
