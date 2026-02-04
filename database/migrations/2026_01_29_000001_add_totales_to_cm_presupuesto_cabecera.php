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
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'monto_gravado')) {
                $table->decimal('monto_gravado', 15, 2)->default(0)->after('observacion');
            }
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'monto_tot_impuesto')) {
                $table->decimal('monto_tot_impuesto', 15, 2)->default(0)->after('monto_gravado');
            }
            if (!Schema::hasColumn('cm_presupuesto_cabecera', 'monto_general')) {
                $table->decimal('monto_general', 15, 2)->default(0)->after('monto_tot_impuesto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_presupuesto_cabecera', function (Blueprint $table) {
            $columns = ['monto_gravado', 'monto_tot_impuesto', 'monto_general'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('cm_presupuesto_cabecera', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
