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
        Schema::table('presupuesto_venta_detalles', function (Blueprint $table) {
            $table->decimal('porcentaje_descuento', 5, 2)->default(0)->after('precio_unitario');
            $table->decimal('monto_descuento', 15, 2)->default(0)->after('porcentaje_descuento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuesto_venta_detalles', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_descuento', 'monto_descuento']);
        });
    }
};
