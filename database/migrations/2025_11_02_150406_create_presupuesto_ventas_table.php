<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('personas', 'cod_persona');
            $table->foreignId('recepcion_vehiculo_id')->constrained('recepcion_vehiculos');
            $table->foreignId('cod_condicion_compra')->constrained('condicion_compra', 'cod_condicion_compra');
            $table->date('fecha_presupuesto');
            $table->string('estado', 50)->default('Pendiente');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_ventas');
    }
};
