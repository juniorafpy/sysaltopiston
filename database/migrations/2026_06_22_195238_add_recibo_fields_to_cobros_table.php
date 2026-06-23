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
        Schema::table('cobros', function (Blueprint $table) {
            $table->integer('cod_timbrado_recibo')->nullable()->after('monto_total');
            $table->string('numero_recibo', 20)->nullable()->after('cod_timbrado_recibo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn(['cod_timbrado_recibo', 'numero_recibo']);
        });
    }
};
