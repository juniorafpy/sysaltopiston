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
        Schema::create('guia_remision_cabecera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_cabecera_id')->constrained('cm_compras_cabecera', 'id_compra_cabecera');
            $table->unsignedBigInteger('almacen_id'); // Asumiendo que tendrás una tabla 'almacenes'
            $table->string('tipo_comprobante', 10)->default('REM');
            $table->string('ser_remision', 10);
            $table->string('numero_remision', 20);
            $table->date('fecha_remision');
            $table->unsignedBigInteger('cod_empleado');
            $table->unsignedBigInteger('cod_sucursal');
            $table->string('usuario_alta');
            $table->timestamp('fec_alta');
            $table->char('estado', 1)->default('P'); // P: Pendiente, C: Completado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guia_remision_cabecera');
    }
};
