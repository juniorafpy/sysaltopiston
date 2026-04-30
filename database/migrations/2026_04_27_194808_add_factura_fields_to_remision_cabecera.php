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
        Schema::table('remision_cabecera', function (Blueprint $table) {
            // Agregar campos de referencia a la factura
            $table->string('tip_factura', 10)->nullable()->after('compra_cabecera_id');
            $table->string('ser_factura', 10)->nullable()->after('tip_factura');
            $table->string('nro_factura', 20)->nullable()->after('ser_factura');
            
            // Crear índice compuesto para mejorar búsquedas
            $table->index(['tip_factura', 'ser_factura', 'nro_factura'], 'idx_remision_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remision_cabecera', function (Blueprint $table) {
            $table->dropIndex('idx_remision_factura');
            $table->dropColumn(['tip_factura', 'ser_factura', 'nro_factura']);
        });
    }
};
