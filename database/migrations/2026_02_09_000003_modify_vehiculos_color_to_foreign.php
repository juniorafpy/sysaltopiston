<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar nueva columna color_id
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->after('anio');
        });

        // Migrar datos existentes si hay
        $vehiculos = DB::table('vehiculos')->whereNotNull('color')->get();
        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->color) {
                // Buscar o crear el color
                $colorId = DB::table('colores')->where('descripcion', $vehiculo->color)->value('cod_color');
                if (!$colorId) {
                    DB::table('colores')->insert([
                        'descripcion' => $vehiculo->color,
                        'usuario_alta' => 'sistema',
                        'fec_alta' => now()
                    ]);
                    $colorId = DB::table('colores')->where('descripcion', $vehiculo->color)->value('cod_color');
                }
                DB::table('vehiculos')->where('id', $vehiculo->id)->update(['color_id' => $colorId]);
            }
        }

        // Eliminar columna color antigua
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('color');
        });

        // Agregar foreign key
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreign('color_id')->references('cod_color')->on('colores')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // Eliminar foreign key
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
        });

        // Agregar columna color antigua
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->after('anio');
        });

        // Migrar datos de vuelta
        $vehiculos = DB::table('vehiculos')->whereNotNull('color_id')->get();
        foreach ($vehiculos as $vehiculo) {
            $colorDesc = DB::table('colores')->where('cod_color', $vehiculo->color_id)->value('descripcion');
            if ($colorDesc) {
                DB::table('vehiculos')->where('id', $vehiculo->id)->update(['color' => $colorDesc]);
            }
        }

        // Eliminar columna color_id
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn('color_id');
        });
    }
};
