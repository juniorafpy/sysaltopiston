<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arqueos_caja', function (Blueprint $table) {
            $table->id('cod_arqueo');
            $table->unsignedBigInteger('cod_apertura');
            $table->decimal('efectivo_sistema', 15, 2)->default(0);
            $table->decimal('tarjetas_sistema', 15, 2)->default(0);
            $table->decimal('transferencias_sistema', 15, 2)->default(0);
            $table->decimal('cheques_sistema', 15, 2)->default(0);
            $table->decimal('total_sistema', 15, 2)->default(0);
            $table->decimal('efectivo_fisico', 15, 2)->default(0);
            $table->decimal('tarjetas_fisico', 15, 2)->default(0);
            $table->decimal('transferencias_fisico', 15, 2)->default(0);
            $table->decimal('cheques_fisico', 15, 2)->default(0);
            $table->decimal('total_fisico', 15, 2)->default(0);
            $table->decimal('diferencia', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('usuario_alta', 100)->nullable();
            $table->timestamp('fecha_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arqueos_caja');
    }
};
