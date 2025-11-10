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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id('cod_factura');

            // Datos del timbrado y numeración
            $table->unsignedBigInteger('cod_timbrado'); // FK a timbrados
            $table->string('numero_factura', 20)->unique(); // Ej: "001-001-0000123"
            $table->date('fecha_factura');

            // Cliente
            $table->unsignedBigInteger('cod_cliente'); // FK a personas

            // Condición de venta
            $table->enum('condicion_venta', ['Contado', 'Crédito'])->default('Contado');

            // Relación opcional con presupuesto
            $table->unsignedBigInteger('presupuesto_venta_id')->nullable(); // FK a presupuesto_ventas

            // Relación opcional con orden de servicio
            $table->unsignedBigInteger('orden_servicio_id')->nullable(); // FK a orden_servicios

            // Totales
            $table->decimal('subtotal_gravado_10', 15, 2)->default(0); // Gravado 10%
            $table->decimal('subtotal_gravado_5', 15, 2)->default(0); // Gravado 5%
            $table->decimal('subtotal_exenta', 15, 2)->default(0); // Exenta
            $table->decimal('total_iva_10', 15, 2)->default(0); // IVA 10%
            $table->decimal('total_iva_5', 15, 2)->default(0); // IVA 5%
            $table->decimal('total_general', 15, 2)->default(0); // Total de la factura

            // Estado
            $table->enum('estado', ['Emitida', 'Anulada', 'Pagada'])->default('Emitida');
            $table->text('observaciones')->nullable();

            // Auditoría
            $table->unsignedBigInteger('cod_sucursal')->nullable();
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_timbrado')->references('cod_timbrado')->on('timbrados')->onDelete('restrict');
            $table->foreign('cod_cliente')->references('cod_persona')->on('personas')->onDelete('restrict');
            // $table->foreign('presupuesto_venta_id')->references('id')->on('presupuesto_ventas')->onDelete('restrict');
            // $table->foreign('orden_servicio_id')->references('id')->on('orden_servicios')->onDelete('restrict');

            // Índices
            $table->index('numero_factura');
            $table->index('cod_cliente');
            $table->index('fecha_factura');
            $table->index('estado');
            $table->index('condicion_venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
