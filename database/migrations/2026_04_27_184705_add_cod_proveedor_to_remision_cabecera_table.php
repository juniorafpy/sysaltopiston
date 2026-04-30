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
            // Agregar campo cod_proveedor después de compra_cabecera_id
            // Permitir NULL porque puede venir de la factura o ser directo
            $table->unsignedBigInteger('cod_proveedor')->nullable()->after('compra_cabecera_id');
            
            // Agregar índice y clave foránea si existe la tabla proveedores
            $table->foreign('cod_proveedor')
                  ->references('cod_proveedor')
                  ->on('proveedores')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remision_cabecera', function (Blueprint $table) {
            $table->dropForeign(['cod_proveedor']);
            $table->dropColumn('cod_proveedor');
        });
    }
};
