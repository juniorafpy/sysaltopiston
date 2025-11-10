<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('st_modelos', function (Blueprint $table) {
            $table->id('cod_modelo');
            $table->string('descripcion', 50);
            $table->foreignId('cod_marca')->constrained('marcas', 'cod_marca');
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_modelos');
    }
};
