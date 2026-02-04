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
            // Verificar si la columna usuario_alta no existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'usuario_alta')) {
                $table->string('usuario_alta')->nullable()->after('cargado');
            }

            // Verificar si la columna fec_alta no existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'fec_alta')) {
                $table->timestamp('fec_alta')->nullable()->after('usuario_alta');
            }

            // Verificar si la columna usuario_modifica no existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'usuario_modifica')) {
                $table->string('usuario_modifica')->nullable()->after('fec_alta');
            }

            // Verificar si la columna fec_modifica no existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'fec_modifica')) {
                $table->timestamp('fec_modifica')->nullable()->after('usuario_modifica');
            }

            // Verificar si la columna observacion no existe
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'observacion')) {
                $table->text('observacion')->nullable()->after('fec_modifica');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_presupuesto_cabecera', function (Blueprint $table) {
            $columns = ['usuario_alta', 'fec_alta', 'usuario_modifica', 'fec_modifica', 'observacion'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('cm_presupuesto_cabecera', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
