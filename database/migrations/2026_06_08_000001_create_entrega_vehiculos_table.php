<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrega_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_servicio_id')->unique();
            $table->foreign('orden_servicio_id')->references('id')->on('orden_servicios')->onDelete('cascade');
            $table->dateTime('fecha_entrega');
            $table->string('persona_recibe', 255);
            $table->string('documento_recibe', 50)->nullable();
            $table->integer('kilometraje_salida')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('usuario_alta', 100)->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega_vehiculos');
    }
};
