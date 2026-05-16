<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidad_mecanico', function (Blueprint $table) {
            $table->id('cod_especialidad');
            $table->string('descripcion', 100);
            $table->string('usuario_alta')->nullable();
            $table->date('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidad_mecanico');
    }
};
