<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_debito_compras', function (Blueprint $table) {
            $table->id('id_nota');

            // Relación con la compra original
            $table->unsignedBigInteger('id_compra_cabecera');
            $table->foreign('id_compra_cabecera')
                  ->references('id_compra_cabecera')
                  ->on('cm_compras_cabecera')
                  ->onDelete('restrict');

            // Datos del proveedor
            $table->unsignedBigInteger('cod_proveedor');
            $table->foreign('cod_proveedor')
                  ->references('cod_proveedor')
                  ->on('proveedores')
                  ->onDelete('restrict');

            // Motivo de la nota
            $table->unsignedBigInteger('cod_motivo');
            $table->foreign('cod_motivo')
                  ->references('cod_motivo')
                  ->on('motivos_nota_credito_debito')
                  ->onDelete('restrict');

            // Datos del comprobante (similar a CompraCabecera)
            $table->string('tip_comprobante', 10); // 'NC' o 'ND'
            $table->string('ser_comprobante', 10);
            $table->string('timbrado', 20);
            $table->string('nro_comprobante', 20);
            $table->date('fec_comprobante');
            $table->text('observacion')->nullable();

            // Auditoría (sin timestamps automáticos como CompraCabecera)
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->dateTime('fecha_alta')->nullable();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->dateTime('fecha_mod')->nullable();

            // Índices
            $table->index('id_compra_cabecera');
            $table->index('cod_proveedor');
            $table->index('cod_motivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_debito_compras');
    }
};
