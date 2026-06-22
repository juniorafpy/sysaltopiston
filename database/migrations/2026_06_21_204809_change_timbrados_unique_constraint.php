<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timbrados', function (Blueprint $table) {
            // Eliminar restricción única simple en numero_timbrado
            $table->dropUnique('timbrados_numero_timbrado_unique');
            
            // Crear restricción única compuesta
            $table->unique(['numero_timbrado', 'cod_sucursal', 'tipo_comprobante']);
        });
    }

    public function down(): void
    {
        Schema::table('timbrados', function (Blueprint $table) {
            $table->dropUnique(['numero_timbrado', 'cod_sucursal', 'tipo_comprobante']);
            $table->unique('numero_timbrado', 'timbrados_numero_timbrado_unique');
        });
    }
};
