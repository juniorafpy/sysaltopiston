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
        Schema::create('tipo_reclamos', function (Blueprint $table) {
            $table->id('cod_tipo_reclamo');
            $table->string('descripcion', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar tipos de reclamo predefinidos
        DB::table('tipo_reclamos')->insert([
            ['descripcion' => 'Falla de Repuesto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['descripcion' => 'Demora en el Servicio', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['descripcion' => 'Calidad de Servicio', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['descripcion' => 'Atención al Cliente', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['descripcion' => 'Precio/Facturación', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['descripcion' => 'Otros', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_reclamos');
    }
};
