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
            // Eliminar el constraint único antiguo (solo numero_remision)
            $table->dropUnique('remision_cabecera_numero_remision_unique');
            
            // Crear constraint único compuesto: proveedor + serie + numero
            $table->unique(['cod_proveedor', 'ser_remision', 'numero_remision'], 'unique_remision_proveedor_serie_numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remision_cabecera', function (Blueprint $table) {
            // Eliminar constraint compuesto
            $table->dropUnique('unique_remision_proveedor_serie_numero');
            
            // Restaurar constraint único simple
            $table->unique('numero_remision', 'remision_cabecera_numero_remision_unique');
        });
    }
};
