<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recepcion_vehiculo_id')->nullable();
            $table->unsignedBigInteger('empleado_id')->nullable();
            $table->dateTime('fecha_diagnostico')->default(now());
            $table->text('diagnostico_mecanico');
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('Pendiente a presupuesto');
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_mod')->nullable();
            $table->timestamp('fec_mod')->nullable();
            $table->timestamps();

            $table->foreign('recepcion_vehiculo_id')
                ->references('id')
                ->on('recepcion_vehiculos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};
