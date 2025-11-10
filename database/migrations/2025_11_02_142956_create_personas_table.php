<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id('cod_persona');
            $table->string('nombres')->nullable();
            $table->string('apellidos')->nullable();
            $table->string('razon_social')->nullable();
            $table->char('sexo', 1)->nullable();
            $table->string('email')->nullable();
            $table->date('fec_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('cod_estado_civil')->nullable()->constrained('estado_civil', 'cod_estado_civil');
            $table->integer('edad')->nullable();
            $table->foreignId('cod_pais')->nullable()->constrained('pais', 'cod_pais');
            $table->foreignId('cod_departamento')->nullable()->constrained('departamentos', 'cod_departamento');
            $table->char('ind_activo', 1)->default('S');
            $table->boolean('ind_juridica')->default(false);
            $table->boolean('ind_fisica')->default(false);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('nro_documento', 20)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
