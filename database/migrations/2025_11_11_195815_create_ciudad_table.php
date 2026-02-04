<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ciudad', function (Blueprint $table) {
            $table->id('cod_ciudad');
            $table->string('descripcion', 100);
            $table->unsignedBigInteger('cod_departamento');

            $table->foreign('cod_departamento')
                  ->references('cod_departamento')
                  ->on('departamentos')
                  ->onDelete('cascade');

            $table->index('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciudad');
    }
};
