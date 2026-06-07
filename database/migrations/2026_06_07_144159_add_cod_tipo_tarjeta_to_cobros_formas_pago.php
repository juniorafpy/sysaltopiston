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
        Schema::table('cobros_formas_pago', function (Blueprint $table) {
            $table->integer('cod_tipo_tarjeta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cobros_formas_pago', function (Blueprint $table) {
            $table->dropColumn('cod_tipo_tarjeta');
        });
    }
};
