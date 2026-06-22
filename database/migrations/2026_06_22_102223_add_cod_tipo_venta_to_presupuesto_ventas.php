<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            $table->integer('cod_tipo_venta')->nullable()->after('cod_sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            $table->dropColumn('cod_tipo_venta');
        });
    }
};