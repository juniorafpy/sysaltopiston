<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('cod_cliente');
            $table->foreignId('cod_persona')
                ->constrained('personas', 'cod_persona')
                ->onDelete('restrict')
                ->unique();
            $table->char('estado', 1)->default('A')->comment('A=Activo, I=Inactivo');
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
