<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cm_compras_cabecera', function (Blueprint $table) {
            $table->id('id_compra_cabecera');
            $table->unsignedBigInteger('cod_sucursal'); // Asumiendo que 'sucursal' se creará
            $table->date('fec_comprobante');
            $table->foreignId('cod_proveedor')->constrained('proveedores', 'cod_proveedor');
            $table->string('tip_comprobante', 10);
            $table->string('ser_comprobante', 10);
            $table->string('timbrado', 20);
            $table->string('nro_comprobante', 20);
            $table->foreignId('cod_condicion_compra')->constrained('condicion_compra', 'cod_condicion_compra');
            $table->date('fec_vencimiento')->nullable();
            $table->string('nro_oc_ref')->nullable();
            $table->text('observacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cm_compras_cabecera');
    }
};
