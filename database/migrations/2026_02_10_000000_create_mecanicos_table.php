<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mecanico', function (Blueprint $table) {
            $table->id('cod_mecanico');
            $table->foreignId('cod_empleado')->constrained('empleados', 'cod_empleado')->onDelete('cascade');
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mecanico');
    }
};
