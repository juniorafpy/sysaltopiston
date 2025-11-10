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
        Schema::create('timbrados', function (Blueprint $table) {
            $table->id('cod_timbrado');
            $table->string('numero_timbrado', 20)->unique(); // Número de timbrado SET
            $table->date('fecha_inicio_vigencia'); // Inicio de vigencia
            $table->date('fecha_fin_vigencia'); // Fin de vigencia

            // Rango de números de factura
            $table->string('numero_inicial', 20); // Ej: "001-001-0000001"
            $table->string('numero_final', 20); // Ej: "001-001-0010000"
            $table->string('numero_actual', 20); // Número actual disponible

            // Punto de expedición
            $table->string('establecimiento', 3)->default('001'); // Ej: 001
            $table->string('punto_expedicion', 3)->default('001'); // Ej: 001

            $table->boolean('activo')->default(true);

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            $table->timestamps();

            // Índices
            $table->index('numero_timbrado');
            $table->index('activo');
            $table->index(['fecha_inicio_vigencia', 'fecha_fin_vigencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timbrados');
    }
};
