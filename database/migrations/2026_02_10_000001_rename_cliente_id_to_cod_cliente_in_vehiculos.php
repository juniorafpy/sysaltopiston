<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si existe la columna cliente_id
        $hasClienteId = DB::getSchemaBuilder()->hasColumn('vehiculos', 'cliente_id');

        if ($hasClienteId) {
            // Eliminar la foreign key existente
            $foreignKeys = DB::select("
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE table_name = 'vehiculos'
                AND constraint_type = 'FOREIGN KEY'
                AND constraint_name LIKE '%cliente_id%'
            ");

            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    DB::statement("ALTER TABLE vehiculos DROP CONSTRAINT {$fk->constraint_name}");
                }
            }

            // Renombrar la columna
            DB::statement('ALTER TABLE vehiculos RENAME COLUMN cliente_id TO cod_cliente');

            // Crear clientes faltantes a partir de los cod_persona usados en vehiculos
            DB::statement("
                INSERT INTO clientes (cod_persona, estado, usuario_alta, fec_alta)
                SELECT DISTINCT v.cod_cliente, 'A', 'MIGRACION', NOW()
                FROM vehiculos v
                LEFT JOIN clientes c ON c.cod_persona = v.cod_cliente
                WHERE c.cod_cliente IS NULL
            ");

            // Convertir el valor de cod_cliente (antes cod_persona) al verdadero cod_cliente de la tabla clientes
            DB::statement('
                UPDATE vehiculos v
                SET cod_cliente = c.cod_cliente
                FROM clientes c
                WHERE c.cod_persona = v.cod_cliente
            ');

            // Crear nueva foreign key
            DB::statement('
                ALTER TABLE vehiculos
                ADD CONSTRAINT vehiculos_cod_cliente_foreign
                FOREIGN KEY (cod_cliente)
                REFERENCES clientes(cod_cliente)
                ON DELETE RESTRICT
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasCodCliente = DB::getSchemaBuilder()->hasColumn('vehiculos', 'cod_cliente');

        if ($hasCodCliente) {
            // Eliminar la foreign key
            DB::statement('ALTER TABLE vehiculos DROP CONSTRAINT IF EXISTS vehiculos_cod_cliente_foreign');

            // Convertir de cod_cliente de clientes a cod_persona para volver al esquema original
            DB::statement('
                UPDATE vehiculos v
                SET cod_cliente = c.cod_persona
                FROM clientes c
                WHERE c.cod_cliente = v.cod_cliente
            ');

            // Renombrar la columna de vuelta
            DB::statement('ALTER TABLE vehiculos RENAME COLUMN cod_cliente TO cliente_id');

            // Restaurar la foreign key original
            DB::statement('
                ALTER TABLE vehiculos
                ADD CONSTRAINT vehiculos_cliente_id_foreign
                FOREIGN KEY (cliente_id)
                REFERENCES personas(cod_persona)
                ON DELETE RESTRICT
            ');
        }
    }
};
