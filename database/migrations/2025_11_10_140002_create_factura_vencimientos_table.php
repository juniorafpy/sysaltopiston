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
        Schema::create('factura_vencimientos', function (Blueprint $table) {
            $table->id('cod_vencimiento');

            // Relación con factura
            $table->unsignedBigInteger('cod_factura');

            // Datos del vencimiento
            $table->integer('nro_cuota'); // 1, 2, 3...
            $table->date('fecha_vencimiento');
            $table->decimal('monto_cuota', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->decimal('saldo_pendiente', 15, 2);

            // Estado
            $table->enum('estado', ['Pendiente', 'Pagado', 'Vencido'])->default('Pendiente');

            $table->timestamps();

            // Foreign keys
            $table->foreign('cod_factura')->references('cod_factura')->on('facturas')->onDelete('cascade');

            // Índices
            $table->index('cod_factura');
            $table->index('fecha_vencimiento');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_vencimientos');
    }
};
