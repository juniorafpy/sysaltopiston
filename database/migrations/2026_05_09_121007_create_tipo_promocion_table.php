<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_promocion', function (Blueprint $table) {
            $table->id('cod_tipo_promocion');
            $table->string('descripcion', 100);
            $table->string('usuario_alta')->nullable();
            $table->date('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_promocion');
    }
};
