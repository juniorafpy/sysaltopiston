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
        Schema::create('existe_stock', function (Blueprint $table) {
            $table->id();

            // Claves foráneas
            $table->unsignedBigInteger('cod_articulo');
            $table->unsignedBigInteger('cod_sucursal');

            // Campos de stock
            $table->decimal('stock_actual', 12, 2)->default(0)->comment('Stock físico disponible en sucursal');
            $table->decimal('stock_reservado', 12, 2)->default(0)->comment('Stock reservado para órdenes de servicio');
            $table->decimal('stock_minimo', 12, 2)->default(0)->comment('Stock mínimo requerido en sucursal');

            // Campos de sistema
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_mod')->nullable();
            $table->timestamp('fec_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_articulo')->references('cod_articulo')->on('articulos')->onDelete('cascade');
            $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursal')->onDelete('cascade');

            // Índice único: un artículo solo puede tener un registro por sucursal
            $table->unique(['cod_articulo', 'cod_sucursal'], 'unique_articulo_sucursal');

            // Índices para búsquedas
            $table->index('cod_articulo');
            $table->index('cod_sucursal');
            $table->index('stock_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('existe_stock');
    }
};
