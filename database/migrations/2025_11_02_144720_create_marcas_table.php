<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id('cod_marca');
            $table->string('descripcion', 50);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas');
    }
};
