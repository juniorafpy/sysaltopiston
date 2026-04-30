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
            // Eliminar la foreign key existente
            $table->dropForeign(['compra_cabecera_id']);
            
            // Hacer la columna nullable
            $table->unsignedBigInteger('compra_cabecera_id')->nullable()->change();
            
            // Volver a crear la foreign key
            $table->foreign('compra_cabecera_id')
                ->references('id_compra_cabecera')
                ->on('cm_compras_cabecera')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remision_cabecera', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['compra_cabecera_id']);
            
            // Hacer la columna NOT NULL nuevamente
            $table->unsignedBigInteger('compra_cabecera_id')->nullable(false)->change();
            
            // Volver a crear la foreign key
            $table->foreign('compra_cabecera_id')
                ->references('id_compra_cabecera')
                ->on('cm_compras_cabecera')
                ->onDelete('restrict');
        });
    }
};
