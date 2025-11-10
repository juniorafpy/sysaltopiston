<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caja_timbrado', function (Blueprint $table) {
            $table->id('cod_caja_timbrado');

            // Relación con caja
            $table->unsignedBigInteger('cod_caja');

            // Relación con timbrado
            $table->unsignedBigInteger('cod_timbrado');

            // Control
            $table->boolean('activo')->default(true);
            $table->date('fecha_asignacion')->default(DB::raw('CURRENT_DATE'));

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_caja')->references('cod_caja')->on('cajas')->onDelete('cascade');
            $table->foreign('cod_timbrado')->references('cod_timbrado')->on('timbrados')->onDelete('cascade');

            // Índices
            $table->index('cod_caja');
            $table->index('cod_timbrado');

            // Una caja solo puede tener un timbrado activo a la vez
            $table->unique(['cod_caja', 'cod_timbrado', 'activo'], 'unique_caja_timbrado_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_timbrado');
    }
};
