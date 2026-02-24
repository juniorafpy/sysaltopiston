<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('presupuesto_ventas', 'cod_condicion')) {
                $table->unsignedBigInteger('cod_condicion')
                    ->nullable()
                    ->after('recepcion_vehiculo_id');

                $table->foreign('cod_condicion')
                    ->references('cod_condicion')
                    ->on('condicion')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            if (Schema::hasColumn('presupuesto_ventas', 'cod_condicion_compra')) {
                $table->dropForeign(['cod_condicion_compra']);
                $table->dropColumn('cod_condicion_compra');
            }
        });
    }
};
