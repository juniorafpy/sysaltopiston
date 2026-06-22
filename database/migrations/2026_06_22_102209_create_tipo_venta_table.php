<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_venta', function (Blueprint $table) {
            $table->integer('cod_tipo_venta')->primary();
            $table->string('descripcion', 100);
            $table->char('estado', 1)->default('A');
            $table->dateTime('fec_alta');
            $table->string('usuario_alta', 100);
        });

        // Insertar registros por defecto
        DB::table('tipo_venta')->insert([
            ['cod_tipo_venta' => 1, 'descripcion' => 'Mostrador', 'estado' => 'A', 'fec_alta' => now(), 'usuario_alta' => 'Sistema'],
            ['cod_tipo_venta' => 2, 'descripcion' => 'OS', 'estado' => 'A', 'fec_alta' => now(), 'usuario_alta' => 'Sistema'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_venta');
    }
};