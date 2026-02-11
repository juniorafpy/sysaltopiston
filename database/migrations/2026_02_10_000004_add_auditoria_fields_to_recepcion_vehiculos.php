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
        Schema::table('recepcion_vehiculos', function (Blueprint $table) {
            // Verificar y agregar columnas faltantes
            if (!DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'cod_sucursal')) {
                $table->unsignedBigInteger('cod_sucursal')->nullable()->after('inventario');
            }

            if (!DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'usuario_alta')) {
                $table->string('usuario_alta')->nullable()->after('cod_sucursal');
            }

            if (!DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'fec_alta')) {
                $table->timestamp('fec_alta')->nullable()->after('usuario_alta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepcion_vehiculos', function (Blueprint $table) {
            $columns = ['fec_alta', 'usuario_alta', 'cod_sucursal'];
            foreach ($columns as $column) {
                if (DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
