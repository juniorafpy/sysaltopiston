<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('cod_empleado');
            $table->date('fec_alta')->nullable();
            $table->foreignId('cod_persona')->constrained('personas', 'cod_persona');
            $table->unsignedBigInteger('cod_cargo')->nullable();
            $table->string('nombre')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
