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
        Schema::create('orden_servicio_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('orden_servicios')->onDelete('cascade');
            $table->foreignId('presupuesto_venta_detalle_id')->nullable()->constrained('presupuesto_venta_detalles')->onDelete('set null');
            $table->unsignedBigInteger('cod_articulo');
            $table->string('descripcion')->nullable();

            // Cantidades
            $table->decimal('cantidad', 12, 2);
            $table->decimal('cantidad_utilizada', 12, 2)->default(0); // Para control de consumo real

            // Precios y descuentos
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->decimal('monto_descuento', 15, 2)->default(0);

            // Impuestos
            $table->decimal('porcentaje_impuesto', 5, 2)->default(10);
            $table->decimal('monto_impuesto', 15, 2);

            // Totales
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);

            // Control de stock
            $table->boolean('stock_reservado')->default(false);
            $table->boolean('stock_descontado')->default(false);
            $table->timestamp('fecha_reserva_stock')->nullable();
            $table->timestamp('fecha_descuento_stock')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('cod_articulo')->references('cod_articulo')->on('articulos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_servicio_detalles');
    }
};
