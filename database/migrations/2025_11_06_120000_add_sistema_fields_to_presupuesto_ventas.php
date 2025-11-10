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
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('cod_sucursal')->nullable()->after('observaciones');
            $table->string('usuario_alta')->nullable()->after('cod_sucursal');
            $table->timestamp('fec_alta')->nullable()->after('usuario_alta');
            $table->string('usuario_mod')->nullable()->after('fec_alta');
            $table->timestamp('fec_mod')->nullable()->after('usuario_mod');

            // Foreign key para sucursal
            $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursal')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            $table->dropForeign(['cod_sucursal']);
            $table->dropColumn(['cod_sucursal', 'usuario_alta', 'fec_alta', 'usuario_mod', 'fec_mod']);
        });
    }
};
