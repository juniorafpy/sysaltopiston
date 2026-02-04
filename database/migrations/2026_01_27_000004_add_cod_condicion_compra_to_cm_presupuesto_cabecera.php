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
        Schema::table('cm_presupuesto_cabecera', function (Blueprint $table) {
            // Verificar si la columna ya existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'cod_condicion_compra')) {
                $table->unsignedBigInteger('cod_condicion_compra')->nullable()->after('cod_sucursal');
                // Comentado temporalmente hasta verificar la estructura de la tabla condicion
                // $table->foreign('cod_condicion_compra')
                //     ->references('cod_condicion_compra')
                //     ->on('condicion')
                //     ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_presupuesto_cabecera', function (Blueprint $table) {
            if (Schema::hasColumn('cm_presupuesto_cabecera', 'cod_condicion_compra')) {
                $table->dropForeign(['cod_condicion_compra']);
                $table->dropColumn('cod_condicion_compra');
            }
        });
    }
};
