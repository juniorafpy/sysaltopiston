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
        Schema::create('estados', function (Blueprint $table) {
            $table->id('cod_estado');
            $table->string('descripcion', 100);
            $table->string('tipo', 50)->nullable(); // Para categorizar estados (orden, factura, etc)
            $table->boolean('activo')->default(true);

            $table->index('tipo');
        });

        // Insertar estados comunes
        DB::table('estados')->insert([
            ['cod_estado' => 1, 'descripcion' => 'Pendiente', 'tipo' => 'general', 'activo' => true],
            ['cod_estado' => 2, 'descripcion' => 'En Proceso', 'tipo' => 'general', 'activo' => true],
            ['cod_estado' => 3, 'descripcion' => 'Completado', 'tipo' => 'general', 'activo' => true],
            ['cod_estado' => 4, 'descripcion' => 'Cancelado', 'tipo' => 'general', 'activo' => true],
            ['cod_estado' => 5, 'descripcion' => 'Aprobado', 'tipo' => 'general', 'activo' => true],
            ['cod_estado' => 6, 'descripcion' => 'Rechazado', 'tipo' => 'general', 'activo' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
};
