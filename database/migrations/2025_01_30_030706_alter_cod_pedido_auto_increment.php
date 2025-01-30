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
        Schema::table('pedidos_cabecera', function (Blueprint $table) {
            $table->bigIncrements('cod_pedido')->change(); // Si es necesario cambiar a 'bigIncrements'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_cabecera', function (Blueprint $table) {
            $table->integer('cod_pedido')->change(); 
        });
    }
};
