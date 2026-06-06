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
        Schema::table('recepcion_vehiculo_items_inventario', function (Blueprint $table) {
            $table->string('nivel_combustible')->nullable()->after('cod_inventario');
            $table->text('observaciones_inventario')->nullable()->after('nivel_combustible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepcion_vehiculo_items_inventario', function (Blueprint $table) {
            //
        });
    }
};
