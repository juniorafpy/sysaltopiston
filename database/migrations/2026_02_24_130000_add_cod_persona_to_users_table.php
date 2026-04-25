<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!DB::getSchemaBuilder()->hasColumn('users', 'cod_persona')) {
                $table->unsignedBigInteger('cod_persona')->nullable()->after('cod_empleado');
            }
        });

        $fkExists = DB::selectOne("SELECT 1
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.table_name = 'users'
              AND tc.constraint_type = 'FOREIGN KEY'
              AND kcu.column_name = 'cod_persona'
            LIMIT 1");

        if (!$fkExists) {
            DB::statement('ALTER TABLE users
                ADD CONSTRAINT users_cod_persona_foreign
                FOREIGN KEY (cod_persona)
                REFERENCES personas(cod_persona)
                ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('users', 'cod_persona')) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_cod_persona_foreign');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cod_persona');
            });
        }
    }
};
