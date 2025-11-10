<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('personas', 'cod_persona');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->dateTime('fecha_recepcion');
            $table->integer('kilometraje');
            $table->text('motivo_ingreso');
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('Ingresado');
            $table->foreignId('empleado_id')->nullable()->constrained('empleados', 'cod_empleado')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_vehiculos');
    }
};
