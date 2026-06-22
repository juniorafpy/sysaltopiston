<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recaudaciones_depositar', function (Blueprint $table) {
            $table->integer('cod_recaudacion')->primary();
            $table->decimal('monto', 15, 2)->default(0);
            $table->date('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recaudaciones_depositar');
    }
};
