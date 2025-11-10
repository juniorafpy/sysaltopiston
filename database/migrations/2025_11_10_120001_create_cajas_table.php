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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id('cod_caja');
            $table->string('descripcion', 100); // Ej: "Caja 1", "Caja Principal", "Caja Mostrador"
            $table->unsignedBigInteger('cod_sucursal')->nullable();
            $table->boolean('activo')->default(true);

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            // $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursales')->onDelete('restrict');

            // Índices
            $table->index('cod_sucursal');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
