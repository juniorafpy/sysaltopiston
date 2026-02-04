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
        Schema::create('cp_cuotas', function (Blueprint $table) {
            $table->id('id_cuota');
            $table->unsignedBigInteger('id_compra_cabecera');
            $table->string('tip_comprobante', 10);
            $table->string('ser_comprobante', 10);
            $table->string('nro_comprobante', 20);
            $table->unsignedBigInteger('cod_proveedor');
            $table->integer('nro_cuota');
            $table->integer('total_cuotas');
            $table->date('fec_cuota');
            $table->date('fec_vencimiento');
            $table->decimal('monto_cuota', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->string('estado', 20)->default('Pendiente');
            $table->text('observacion')->nullable();
            $table->integer('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->nullable();
            $table->integer('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            // Foreign keys
            $table->foreign('id_compra_cabecera')
                ->references('id_compra_cabecera')
                ->on('cm_compras_cabecera')
                ->onDelete('cascade');

            $table->foreign('cod_proveedor')
                ->references('cod_proveedor')
                ->on('proveedores')
                ->onDelete('restrict');

            // Indices
            $table->index('id_compra_cabecera');
            $table->index('cod_proveedor');
            $table->index('estado');
            $table->index('fec_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_cuotas');
    }
};
