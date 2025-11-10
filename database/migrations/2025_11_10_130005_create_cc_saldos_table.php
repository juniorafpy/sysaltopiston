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
        Schema::create('cc_saldos', function (Blueprint $table) {
            $table->id('cod_saldo');

            // Cliente
            $table->unsignedBigInteger('cod_cliente'); // FK a personas

            // Datos del comprobante
            $table->string('tipo_comprobante', 50); // 'Factura', 'Recibo', 'Nota Crédito', 'Nota Débito'
            $table->string('nro_comprobante', 20); // Número del comprobante
            $table->date('fecha_comprobante'); // Fecha del comprobante

            // Importes
            $table->decimal('debe', 15, 2)->default(0); // Monto que debe (factura)
            $table->decimal('haber', 15, 2)->default(0); // Monto que se paga (recibo)
            $table->decimal('saldo_actual', 15, 2); // Saldo después de este movimiento

            // Descripción
            $table->text('descripcion')->nullable();

            // Relación con la factura (si aplica)
            $table->unsignedBigInteger('cod_factura')->nullable(); // FK a facturas

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_cliente')->references('cod_persona')->on('personas')->onDelete('restrict');
            $table->foreign('cod_factura')->references('cod_factura')->on('facturas')->onDelete('cascade');

            // Índices
            $table->index('cod_cliente');
            $table->index('tipo_comprobante');
            $table->index('nro_comprobante');
            $table->index('fecha_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cc_saldos');
    }
};
