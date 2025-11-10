<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicion_compra', function (Blueprint $table) {
            $table->id('cod_condicion_compra');
            $table->string('descripcion');
            $table->integer('dias_cuotas')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicion_compra');
    }
};
