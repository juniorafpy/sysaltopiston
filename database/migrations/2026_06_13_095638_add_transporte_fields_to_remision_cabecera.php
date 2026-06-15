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
        Schema::table('remision_cabecera', function (Blueprint $table) {
            $table->string('marca_vehiculo', 100)->nullable()->after('observacion');
            $table->string('matricula_vehiculo', 20)->nullable()->after('marca_vehiculo');
            $table->string('nombre_chofer', 150)->nullable()->after('matricula_vehiculo');
            $table->string('documento_chofer', 30)->nullable()->after('nombre_chofer');
            $table->string('telefono_chofer', 30)->nullable()->after('documento_chofer');
            $table->string('motivo_traslado', 100)->nullable()->after('telefono_chofer');
            $table->string('punto_partida', 200)->nullable()->after('motivo_traslado');
            $table->string('punto_llegada', 200)->nullable()->after('punto_partida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remision_cabecera', function (Blueprint $table) {
            $table->dropColumn([
                'marca_vehiculo',
                'matricula_vehiculo',
                'nombre_chofer',
                'documento_chofer',
                'telefono_chofer',
                'motivo_traslado',
                'punto_partida',
                'punto_llegada',
            ]);
        });
    }
};
