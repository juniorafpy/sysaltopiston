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
        Schema::create('aperturas_caja', function (Blueprint $table) {
            $table->id('cod_apertura');
            $table->unsignedBigInteger('cod_caja'); // FK a cajas
            $table->unsignedBigInteger('cod_cajero'); // FK a users
            $table->unsignedBigInteger('cod_sucursal')->nullable(); // Sucursal donde se abre

            // Datos de Apertura
            $table->date('fecha_apertura');
            $table->time('hora_apertura');
            $table->decimal('monto_inicial', 15, 2)->default(0); // Efectivo con el que se abre
            $table->text('observaciones_apertura')->nullable();

            // Datos de Cierre (nullable hasta que se cierre)
            $table->date('fecha_cierre')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->decimal('efectivo_real', 15, 2)->nullable(); // Efectivo físico contado
            $table->decimal('saldo_esperado', 15, 2)->nullable(); // Calculado del sistema
            $table->decimal('diferencia', 15, 2)->nullable(); // efectivo_real - saldo_esperado
            $table->decimal('monto_depositar', 15, 2)->nullable(); // Monto a depositar
            $table->text('observaciones_cierre')->nullable();

            // Estado: 'Abierta', 'Cerrada'
            $table->enum('estado', ['Abierta', 'Cerrada'])->default('Abierta');

            // Auditoría
            $table->unsignedBigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->useCurrent();
            $table->unsignedBigInteger('usuario_mod')->nullable();
            $table->timestamp('fecha_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_caja')->references('cod_caja')->on('cajas')->onDelete('restrict');
            $table->foreign('cod_cajero')->references('id')->on('users')->onDelete('restrict');
            // $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursales')->onDelete('restrict');

            // Índices
            $table->index('cod_caja');
            $table->index('cod_cajero');
            $table->index('estado');
            $table->index('fecha_apertura');

            // Constraint: Solo una caja abierta a la vez por cajero
            // Se validará en el código
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aperturas_caja');
    }
};
