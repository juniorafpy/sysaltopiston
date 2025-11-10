<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pais', function (Blueprint $table) {
            $table->id('cod_pais');
            $table->string('descripcion', 50);
            $table->string('gentilicio', 20);
            $table->string('abreviatura', 3);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pais');
    }
};
