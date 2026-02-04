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
        // Si la tabla ya existe, no hacer nada
        if (Schema::hasTable('cm_presupuesto_cabecera')) {
            return;
        }

        Schema::create('cm_presupuesto_cabecera', function (Blueprint $table) {
            $table->id('nro_presupuesto');
            $table->unsignedBigInteger('cod_proveedor');
            $table->date('fec_presupuesto');
            $table->unsignedBigInteger('nro_pedido_ref')->nullable();
            $table->unsignedBigInteger('cod_sucursal');
            $table->unsignedBigInteger('cod_condicion_compra');
            $table->string('estado', 20)->default('PENDIENTE');
            $table->boolean('cargado')->default(false);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_modifica')->nullable();
            $table->timestamp('fec_modifica')->nullable();

            // Foreign keys
            $table->foreign('cod_proveedor')->references('cod_proveedor')->on('proveedores')->onDelete('restrict');
            $table->foreign('nro_pedido_ref')->references('cod_pedido')->on('pedidos_cabeceras')->onDelete('set null');
            $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursal')->onDelete('restrict');
            $table->foreign('cod_condicion_compra')->references('cod_condicion_compra')->on('condicion_compra')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_presupuesto_cabecera');
    }
};
