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
        // Verificar si la columna ya existe
        $hasColumn = DB::select("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'nota_credito_debito_compras'
            AND column_name = 'cod_motivo'
        ");

        if (empty($hasColumn)) {
            Schema::table('nota_credito_debito_compras', function (Blueprint $table) {
                // Agregar columna cod_motivo después de cod_proveedor
                $table->unsignedBigInteger('cod_motivo')->after('cod_proveedor')->nullable();

                // Agregar foreign key
                $table->foreign('cod_motivo')
                      ->references('cod_motivo')
                      ->on('motivos_nota_credito_debito')
                      ->onDelete('restrict');

                // Agregar índice
                $table->index('cod_motivo');
            });

            echo "✓ Columna cod_motivo agregada exitosamente\n";
        } else {
            echo "✓ La columna cod_motivo ya existe\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_credito_debito_compras', function (Blueprint $table) {
            $table->dropForeign(['cod_motivo']);
            $table->dropIndex(['cod_motivo']);
            $table->dropColumn('cod_motivo');
        });
    }
};
