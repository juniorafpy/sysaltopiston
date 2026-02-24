<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->unsignedBigInteger('cod_sucursal')->nullable()->after('recepcion_vehiculo_id');

            $table->foreign('cod_sucursal')
                ->references('cod_sucursal')
                ->on('sucursal')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->dropForeign(['cod_sucursal']);
            $table->dropColumn('cod_sucursal');
        });
    }
};
