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
        Schema::create('libro_iva', function (Blueprint $table) {
            $table->id('cod_libro_iva');

            // Datos de la factura
            $table->date('fecha'); // Fecha de la factura
            $table->string('timbrado', 20); // Número de timbrado
            $table->string('numero_factura', 20); // Número de factura

            // Datos del cliente
            $table->string('ruc_cliente', 20)->nullable(); // RUC del cliente
            $table->string('razon_social', 255); // Razón social o nombre completo

            // Gravadas 10%
            $table->decimal('gravado_10', 15, 2)->default(0); // Monto gravado al 10%
            $table->decimal('iva_10', 15, 2)->default(0); // IVA 10%

            // Gravadas 5%
            $table->decimal('gravado_5', 15, 2)->default(0); // Monto gravado al 5%
            $table->decimal('iva_5', 15, 2)->default(0); // IVA 5%

            // Exentas
            $table->decimal('exentas', 15, 2)->default(0); // Monto exento

            // Total
            $table->decimal('total', 15, 2); // Total de la factura

            // Tipo de operación
            $table->enum('tipo_operacion', ['Venta', 'Compra'])->default('Venta');

            // Relación con la factura
            $table->unsignedBigInteger('cod_factura')->nullable(); // FK a facturas

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_factura')->references('cod_factura')->on('facturas')->onDelete('cascade');

            // Índices
            $table->index('fecha');
            $table->index('timbrado');
            $table->index('numero_factura');
            $table->index('ruc_cliente');
            $table->index('tipo_operacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libro_iva');
    }
};
