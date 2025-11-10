<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id('cod_proveedor');
            $table->foreignId('cod_persona')->constrained('personas', 'cod_persona');
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->boolean('estado')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
