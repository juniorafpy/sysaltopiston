<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colores', function (Blueprint $table) {
            $table->id('cod_color');
            $table->string('descripcion', 50);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colores');
    }
};
