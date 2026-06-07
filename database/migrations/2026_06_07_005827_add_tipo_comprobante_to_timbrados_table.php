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
        Schema::table('timbrados', function (Blueprint $table) {
            $table->string('tipo_comprobante', 10)->default('FAC')->after('numero_timbrado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timbrados', function (Blueprint $table) {
            $table->dropColumn('tipo_comprobante');
        });
    }
};
