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
        // Esta migración fue reemplazada por 2025_11_14_235620_create_existe_stock_table_fixed.php
        // La tabla ya existe en la base de datos, no hacer nada
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('existe_stock');
    }
};
