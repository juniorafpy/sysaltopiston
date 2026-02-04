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
        Schema::table('pedidos_cabeceras', function (Blueprint $table) {
            $table->date('fec_pedido')->nullable()->after('cod_sucursal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_cabeceras', function (Blueprint $table) {
            $table->dropColumn('fec_pedido');
        });
    }
};
