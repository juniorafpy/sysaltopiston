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
        Schema::create('tipo_repuesto', function (Blueprint $table) {
            $table->id('cod_tipo_repuesto');
            $table->string('descripcion', 100);
            $table->string('icono', 50)->nullable()->comment('Icono heroicon para UI');
            $table->string('color', 20)->nullable()->comment('Color para badges');
            $table->boolean('activo')->default(true);
            $table->string('usuario_alta', 100)->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_mod', 100)->nullable();
            $table->timestamp('fec_mod')->nullable();
        });

        // Insertar categorías iniciales
        DB::table('tipo_repuesto')->insert([
            ['descripcion' => 'Filtros', 'icono' => 'heroicon-o-funnel', 'color' => 'blue', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Frenos', 'icono' => 'heroicon-o-stop', 'color' => 'red', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Motor', 'icono' => 'heroicon-o-cog', 'color' => 'orange', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Suspensión', 'icono' => 'heroicon-o-arrows-up-down', 'color' => 'purple', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Transmisión', 'icono' => 'heroicon-o-cog-6-tooth', 'color' => 'gray', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Eléctrico', 'icono' => 'heroicon-o-bolt', 'color' => 'yellow', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Lubricantes', 'icono' => 'heroicon-o-beaker', 'color' => 'green', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Refrigeración', 'icono' => 'heroicon-o-snowflake', 'color' => 'cyan', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Escape', 'icono' => 'heroicon-o-cloud', 'color' => 'slate', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Carrocería', 'icono' => 'heroicon-o-truck', 'color' => 'indigo', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Iluminación', 'icono' => 'heroicon-o-light-bulb', 'color' => 'amber', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
            ['descripcion' => 'Sensores', 'icono' => 'heroicon-o-signal', 'color' => 'teal', 'activo' => true, 'usuario_alta' => 'System', 'fec_alta' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_repuesto');
    }
};
