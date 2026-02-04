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
        Schema::create('rucs', function (Blueprint $table) {
            $table->string('ruc', 20)->primary()->comment('Número de RUC');
            $table->string('nombre', 200)->comment('Nombre o razón social');
            $table->string('div', 1)->nullable()->comment('Dígito verificador');
            
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rucs');
    }
};
