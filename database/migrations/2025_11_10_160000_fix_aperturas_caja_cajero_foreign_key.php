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
        Schema::table('aperturas_caja', function (Blueprint $table) {
            // Eliminar foreign key antigua que apunta a users
            $table->dropForeign(['cod_cajero']);

            // Crear nueva foreign key que apunta a empleados
            $table->foreign('cod_cajero')
                ->references('cod_empleado')
                ->on('empleados')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aperturas_caja', function (Blueprint $table) {
            // Revertir: eliminar FK a empleados
            $table->dropForeign(['cod_cajero']);

            // Restaurar FK original a users
            $table->foreign('cod_cajero')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }
};
