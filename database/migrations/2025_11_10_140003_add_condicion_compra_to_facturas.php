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
        Schema::table('facturas', function (Blueprint $table) {
            // Agregar columna cod_condicion_compra
            $table->unsignedBigInteger('cod_condicion_compra')->nullable()->after('condicion_venta');

            // Agregar foreign key
            $table->foreign('cod_condicion_compra')
                  ->references('cod_condicion_compra')
                  ->on('condicion_compra')
                  ->onDelete('restrict');

            // Agregar índice
            $table->index('cod_condicion_compra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['cod_condicion_compra']);
            $table->dropIndex(['cod_condicion_compra']);
            $table->dropColumn('cod_condicion_compra');
        });
    }
};
