<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('presupuesto_venta_detalles')) {
            return;
        }

        Schema::create('presupuesto_venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_venta_id')
                ->constrained('presupuesto_ventas')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('cod_articulo');
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('porcentaje_impuesto', 5, 2)->default(0);
            $table->decimal('monto_impuesto', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('cod_articulo')
                ->references('cod_articulo')
                ->on('articulos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_venta_detalles');
    }
};
