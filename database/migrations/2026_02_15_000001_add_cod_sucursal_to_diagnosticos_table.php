<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            if (!DB::getSchemaBuilder()->hasColumn('diagnosticos', 'cod_sucursal')) {
                $table->unsignedBigInteger('cod_sucursal')->nullable()->after('recepcion_vehiculo_id');
            }
        });

        $fkExists = DB::selectOne("SELECT 1
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'diagnosticos'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'cod_sucursal'
            LIMIT 1");

        if (!$fkExists) {
            DB::statement('ALTER TABLE diagnosticos
                ADD CONSTRAINT diagnosticos_cod_sucursal_foreign
                FOREIGN KEY (cod_sucursal)
                REFERENCES sucursal(cod_sucursal)
                ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('diagnosticos', 'cod_sucursal')) {
            DB::statement('ALTER TABLE diagnosticos DROP CONSTRAINT IF EXISTS diagnosticos_cod_sucursal_foreign');

            Schema::table('diagnosticos', function (Blueprint $table) {
                $table->dropColumn('cod_sucursal');
            });
        }
    }
};
