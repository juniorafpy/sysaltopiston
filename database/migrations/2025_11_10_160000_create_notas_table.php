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
        // Tabla principal de notas (crédito y débito)
        Schema::create('notas', function (Blueprint $table) {
            $table->id('cod_nota');

            // Tipo de nota
            $table->enum('tipo_nota', ['credito', 'debito']);

            // Tipo de operación (solo para notas de crédito)
            $table->enum('tipo_operacion', ['anulacion', 'devolucion', 'otros'])->nullable();

            // Factura de referencia
            $table->unsignedBigInteger('cod_factura');

            // Datos del timbrado y numeración
            $table->unsignedBigInteger('cod_timbrado'); // FK a timbrados
            $table->string('numero_nota', 20)->unique(); // Ej: "001-001-0000123"
            $table->date('fecha_emision');

            // Motivo
            $table->text('motivo');

            // Totales (desglose igual que factura)
            $table->decimal('subtotal_gravado_10', 15, 2)->default(0);
            $table->decimal('subtotal_gravado_5', 15, 2)->default(0);
            $table->decimal('subtotal_exenta', 15, 2)->default(0);
            $table->decimal('total_iva_10', 15, 2)->default(0);
            $table->decimal('total_iva_5', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2); // Monto total de la nota

            // Estado
            $table->enum('estado', ['Emitida', 'Anulada'])->default('Emitida');

            // Observaciones adicionales
            $table->text('observaciones')->nullable();

            // Auditoría
            $table->unsignedBigInteger('cod_sucursal')->nullable();
            $table->unsignedBigInteger('usuario_alta');
            $table->timestamp('fecha_alta')->useCurrent();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_factura')->references('cod_factura')->on('facturas')->onDelete('restrict');
            $table->foreign('cod_timbrado')->references('cod_timbrado')->on('timbrados')->onDelete('restrict');
            $table->foreign('usuario_alta')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('usuario_mod')->references('id')->on('users')->onDelete('restrict');

            // Índices
            $table->index('tipo_nota');
            $table->index('cod_factura');
            $table->index('fecha_emision');
            $table->index('estado');
            $table->index('numero_nota');
        });

        // Tabla de detalle de notas (items/conceptos)
        Schema::create('notas_detalle', function (Blueprint $table) {
            $table->id('cod_nota_detalle');

            // Nota a la que pertenece
            $table->unsignedBigInteger('cod_nota');

            // Detalle del item/concepto
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 15, 2);

            // Exención o gravamen
            $table->enum('tipo_iva', ['Exenta', '5%', '10%']);

            // Subtotales del item
            $table->decimal('subtotal', 15, 2); // cantidad * precio_unitario
            $table->decimal('monto_iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2); // subtotal + monto_iva

            // Referencia opcional al detalle de factura original
            $table->unsignedBigInteger('cod_factura_detalle')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_nota')->references('cod_nota')->on('notas')->onDelete('cascade');
            // Nota: La FK a facturas_detalle está comentada porque esa tabla puede no existir
            // $table->foreign('cod_factura_detalle')->references('cod_factura_detalle')->on('facturas_detalle')->onDelete('set null');

            // Índices
            $table->index('cod_nota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_detalle');
        Schema::dropIfExists('notas');
    }
};
