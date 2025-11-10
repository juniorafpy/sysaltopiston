<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('presupuesto_ventas', 'diagnostico_id')) {
                $table->foreignId('diagnostico_id')
                    ->nullable()
                    ->after('recepcion_vehiculo_id')
                    ->constrained('diagnosticos')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('presupuesto_ventas', 'recepcion_vehiculo_id')) {
            DB::statement('ALTER TABLE presupuesto_ventas ALTER COLUMN recepcion_vehiculo_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('presupuesto_ventas', 'diagnostico_id')) {
            Schema::table('presupuesto_ventas', function (Blueprint $table) {
                $table->dropForeign(['diagnostico_id']);
                $table->dropColumn('diagnostico_id');
            });
        }

        if (Schema::hasColumn('presupuesto_ventas', 'recepcion_vehiculo_id')) {
            $fallbackRecepcionId = DB::table('recepcion_vehiculos')->orderBy('id')->value('id');

            if ($fallbackRecepcionId !== null) {
                DB::table('presupuesto_ventas')
                    ->whereNull('recepcion_vehiculo_id')
                    ->update(['recepcion_vehiculo_id' => $fallbackRecepcionId]);

                DB::statement('ALTER TABLE presupuesto_ventas ALTER COLUMN recepcion_vehiculo_id SET NOT NULL');
            }
        }
    }
};
