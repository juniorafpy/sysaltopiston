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
        Schema::table('articulos', function (Blueprint $table) {
            $table->decimal('stock_actual', 12, 2)->default(0)->after('precio');
            $table->decimal('stock_reservado', 12, 2)->default(0)->after('stock_actual');
            $table->decimal('stock_minimo', 12, 2)->default(0)->after('stock_reservado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn(['stock_actual', 'stock_reservado', 'stock_minimo']);
        });
    }
};
