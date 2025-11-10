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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id('cod_movimiento');
            $table->unsignedBigInteger('cod_apertura'); // FK a aperturas_caja

            // Tipo de movimiento: 'Ingreso', 'Egreso'
            $table->enum('tipo_movimiento', ['Ingreso', 'Egreso']);

            // Concepto: 'Venta', 'Gasto', 'Retiro', 'Deposito', 'Ajuste', etc.
            $table->string('concepto', 100);

            // Referencia al documento original (factura, recibo, etc.)
            $table->string('tipo_documento', 50)->nullable(); // 'Factura', 'Recibo', 'Nota Venta'
            $table->unsignedBigInteger('documento_id')->nullable(); // ID del documento

            $table->decimal('monto', 15, 2);
            $table->text('descripcion')->nullable();
            $table->datetime('fecha_movimiento');

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_apertura')->references('cod_apertura')->on('aperturas_caja')->onDelete('cascade');

            // Índices
            $table->index('cod_apertura');
            $table->index('tipo_movimiento');
            $table->index('concepto');
            $table->index('fecha_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
