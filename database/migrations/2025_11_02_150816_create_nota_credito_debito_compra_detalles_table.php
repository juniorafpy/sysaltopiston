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
        Schema::create('nota_credito_debito_compra_detalles', function (Blueprint $table) {
            $table->id('id_detalle');

            // Relación con la nota de crédito/débito
            $table->unsignedBigInteger('id_nota');
            $table->foreign('id_nota')
                  ->references('id_nota')
                  ->on('nota_credito_debito_compras')
                  ->onDelete('cascade');

            // Relación con artículo
            $table->unsignedBigInteger('cod_articulo');
            $table->foreign('cod_articulo')
                  ->references('cod_articulo')
                  ->on('articulos')
                  ->onDelete('restrict');

            // Datos del detalle (misma estructura que cm_compras_detalle)
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('porcentaje_iva', 5, 2)->default(10.00);
            $table->decimal('monto_total_linea', 15, 2);

            // Índices
            $table->index('id_nota');
            $table->index('cod_articulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_credito_debito_compra_detalles');
    }
};
