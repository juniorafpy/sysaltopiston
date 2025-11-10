<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('marcas', 'cod_marca');
            $table->foreignId('modelo_id')->constrained('st_modelos', 'cod_modelo');
            $table->string('matricula');
            $table->string('anio');
            $table->string('color');
            $table->foreignId('cliente_id')->constrained('personas', 'cod_persona');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
