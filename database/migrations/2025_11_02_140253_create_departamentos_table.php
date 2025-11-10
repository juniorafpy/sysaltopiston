<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id('cod_departamento');
            $table->string('descripcion');
            $table->foreignId('cod_pais')->constrained('pais', 'cod_pais');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
