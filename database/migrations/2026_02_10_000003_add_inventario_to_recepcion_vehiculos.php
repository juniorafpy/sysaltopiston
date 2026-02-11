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
        $hasInventario = DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'inventario');

        if (!$hasInventario) {
            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->json('inventario')->nullable()->after('empleado_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'inventario')) {
            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->dropColumn('inventario');
            });
        }
    }
};
