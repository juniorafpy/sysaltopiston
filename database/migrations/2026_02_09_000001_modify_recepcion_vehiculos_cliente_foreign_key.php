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
        // ====================================
        // RECEPCION_VEHICULOS
        // ====================================

        // Verificar qué columna existe
        $hasClienteId = DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'cliente_id');
        $hasCodCliente = DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'cod_cliente');

        if ($hasClienteId) {
            // Obtener el nombre de la foreign key existente
            $foreignKey = DB::select("
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_name = 'recepcion_vehiculos'
                AND constraint_type = 'FOREIGN KEY'
                AND constraint_name LIKE '%cliente_id%'
            ");

            // Eliminar foreign key si existe
            if (!empty($foreignKey)) {
                DB::statement("ALTER TABLE recepcion_vehiculos DROP CONSTRAINT {$foreignKey[0]->constraint_name}");
            }

            // Renombrar columna
            DB::statement('ALTER TABLE recepcion_vehiculos RENAME COLUMN cliente_id TO cod_cliente');

            // Agregar nueva foreign key
            DB::statement('
                ALTER TABLE recepcion_vehiculos
                ADD CONSTRAINT recepcion_vehiculos_cod_cliente_foreign
                FOREIGN KEY (cod_cliente)
                REFERENCES clientes(cod_cliente)
                ON DELETE RESTRICT
            ');
        } elseif ($hasCodCliente) {
            // La columna ya es cod_cliente, solo verificar/actualizar la foreign key

            // Obtener foreign keys existentes en cod_cliente
            $foreignKey = DB::select("
                SELECT tc.constraint_name, ccu.table_name AS foreign_table
                FROM information_schema.table_constraints tc
                JOIN information_schema.constraint_column_usage ccu
                    ON tc.constraint_name = ccu.constraint_name
                    AND tc.constraint_schema = ccu.constraint_schema
                JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.constraint_schema = kcu.constraint_schema
                WHERE tc.table_name = 'recepcion_vehiculos'
                AND tc.constraint_type = 'FOREIGN KEY'
                AND kcu.column_name = 'cod_cliente'
            ");

            // Si apunta a personas, cambiar a clientes
            if (!empty($foreignKey) && $foreignKey[0]->foreign_table === 'personas') {
                DB::statement("ALTER TABLE recepcion_vehiculos DROP CONSTRAINT {$foreignKey[0]->constraint_name}");

                DB::statement('
                    ALTER TABLE recepcion_vehiculos
                    ADD CONSTRAINT recepcion_vehiculos_cod_cliente_foreign
                    FOREIGN KEY (cod_cliente)
                    REFERENCES clientes(cod_cliente)
                    ON DELETE RESTRICT
                ');
            } elseif (empty($foreignKey)) {
                // No hay foreign key, agregarla
                DB::statement('
                    ALTER TABLE recepcion_vehiculos
                    ADD CONSTRAINT recepcion_vehiculos_cod_cliente_foreign
                    FOREIGN KEY (cod_cliente)
                    REFERENCES clientes(cod_cliente)
                    ON DELETE RESTRICT
                ');
            }
        }

        // ====================================
        // VEHICULOS - SE MANTIENE CON cliente_id
        // No se modifica, vehiculos sigue usando cliente_id apuntando a personas
        // ====================================
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir recepcion_vehiculos
        if (DB::getSchemaBuilder()->hasColumn('recepcion_vehiculos', 'cod_cliente')) {
            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->dropForeign(['cod_cliente']);
            });

            DB::statement('ALTER TABLE recepcion_vehiculos RENAME COLUMN cod_cliente TO cliente_id');

            Schema::table('recepcion_vehiculos', function (Blueprint $table) {
                $table->foreign('cliente_id')
                    ->references('cod_persona')
                    ->on('personas')
                    ->onDelete('restrict');
            });
        }
    }
};
