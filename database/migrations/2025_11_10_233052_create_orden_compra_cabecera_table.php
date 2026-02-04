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
        Schema::create('orden_compra_cabecera', function (Blueprint $table) {
            $table->id('nro_orden_compra');

            // Datos de la orden
            $table->date('fec_orden');
            $table->string('nro_presupuesto_ref', 50)->nullable();
            $table->date('fec_entrega')->nullable();

            // Relaciones
            $table->unsignedBigInteger('cod_proveedor');
            $table->foreign('cod_proveedor')
                  ->references('cod_proveedor')
                  ->on('proveedores')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('cod_condicion_compra');
            $table->foreign('cod_condicion_compra')
                  ->references('cod_condicion_compra')
                  ->on('condicion_compra')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('cod_sucursal')->nullable();
            $table->foreign('cod_sucursal')
                  ->references('cod_sucursal')
                  ->on('sucursal')
                  ->onDelete('set null');

            // Estado
            $table->unsignedBigInteger('estado')->nullable();
            $table->foreign('estado')
                  ->references('cod_estado')
                  ->on('estados')
                  ->onDelete('set null');

            // Campos adicionales
            $table->text('observacion')->nullable();

            // Auditoría
            $table->string('usuario_alta', 100)->nullable();
            $table->string('fec_alta', 20)->nullable(); // Mantener como string por compatibilidad con el modelo
            $table->string('usuario_modifica', 100)->nullable();
            $table->timestamp('fec_modifica')->nullable();

            // Timestamps estándar
            $table->timestamps();

            // Índices
            $table->index('cod_proveedor');
            $table->index('cod_condicion_compra');
            $table->index('fec_orden');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_cabecera');
    }
};
