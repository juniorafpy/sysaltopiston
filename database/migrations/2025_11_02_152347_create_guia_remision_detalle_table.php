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
        Schema::create('guia_remision_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_cabecera_id')->constrained('guia_remision_cabecera')->onDelete('cascade');
            $table->unsignedBigInteger('articulo_id'); // Asumiendo que tendrás una tabla 'articulos'
            $table->integer('cantidad_recibida');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guia_remision_detalle');
    }
};
