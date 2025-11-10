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
        Schema::create('promocion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->onDelete('cascade');
            $table->unsignedBigInteger('articulo_id');
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->timestamps();

            // Foreign key manual para articulos que usa cod_articulo como PK
            $table->foreign('articulo_id')
                ->references('cod_articulo')
                ->on('articulos')
                ->onDelete('cascade');

            // Un artículo solo puede estar una vez en una promoción
            $table->unique(['promocion_id', 'articulo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocion_detalles');
    }
};
