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
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id('cod_detalle');
            $table->unsignedBigInteger('cod_factura'); // FK a facturas

            // Artículo/Servicio
            $table->unsignedBigInteger('cod_articulo'); // FK a articulos
            $table->string('descripcion', 255); // Descripción del artículo

            // Cantidades y precios
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 15, 2);

            // Descuentos
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->decimal('monto_descuento', 15, 2)->default(0);

            // Subtotales
            $table->decimal('subtotal', 15, 2); // (cantidad * precio) - descuento

            // IVA
            $table->enum('tipo_iva', ['10', '5', 'Exenta'])->default('10');
            $table->decimal('porcentaje_iva', 5, 2)->default(10);
            $table->decimal('monto_iva', 15, 2)->default(0);

            // Total del detalle
            $table->decimal('total', 15, 2); // subtotal + iva

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_factura')->references('cod_factura')->on('facturas')->onDelete('cascade');
            $table->foreign('cod_articulo')->references('cod_articulo')->on('articulos')->onDelete('restrict');

            // Índices
            $table->index('cod_factura');
            $table->index('cod_articulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
