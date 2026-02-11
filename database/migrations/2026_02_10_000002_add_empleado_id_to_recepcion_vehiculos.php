<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la columna no existe
        $hasEmpleadoId = DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'empleado_id');

        if (!$hasEmpleadoId) {
            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->foreignId('empleado_id')
                    ->nullable()
                    ->after('estado')
                    ->constrained('empleados', 'cod_empleado')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'empleado_id')) {
            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->dropForeign(['empleado_id']);
                $table->dropColumn('empleado_id');
            });
        }
    }
};
