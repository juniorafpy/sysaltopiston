<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entidades_bancarias', function (Blueprint $table) {
            $table->id('cod_entidad_bancaria');
            $table->string('nombre', 100);
            $table->string('abreviatura', 20)->nullable();
            $table->char('ind_activo', 1)->default('S');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entidades_bancarias');
    }
};
