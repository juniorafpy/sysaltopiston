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
        // Eliminar tablas si existen (para re-crear)
        Schema::dropIfExists('remision_detalle');
        Schema::dropIfExists('remision_cabecera');

        // Tabla remision_cabecera
        Schema::create('remision_cabecera', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_cabecera_id');
            $table->unsignedBigInteger('almacen_id')->nullable(); // Sin FK - referencia a existencia o depósito
            $table->string('tipo_comprobante', 10)->default('REM');
            $table->string('ser_remision', 10);
            $table->string('numero_remision', 20)->unique();
            $table->date('fecha_remision');
            $table->unsignedBigInteger('cod_empleado')->nullable();
            $table->unsignedBigInteger('cod_sucursal')->default(1);
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->useCurrent();
            $table->string('usuario_mod')->nullable();
            $table->timestamp('fec_mod')->nullable();
            $table->char('estado', 1)->default('P'); // P: Pendiente, A: Aprobado, N: Anulado
            $table->text('observacion')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('compra_cabecera_id')
                ->references('id_compra_cabecera')
                ->on('cm_compras_cabecera')
                ->onDelete('restrict');

            $table->foreign('cod_empleado')
                ->references('cod_empleado')
                ->on('empleados')
                ->onDelete('restrict');

            $table->foreign('cod_sucursal')
                ->references('cod_sucursal')
                ->on('sucursal')
                ->onDelete('restrict');
        });

        // Tabla remision_detalle
        Schema::create('remision_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guia_remision_cabecera_id');
            $table->unsignedBigInteger('articulo_id');
            $table->decimal('cantidad_recibida', 10, 2)->default(0);
            $table->decimal('cantidad_devuelta', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('guia_remision_cabecera_id', 'fk_remision_detalle_cabecera')
                ->references('id')
                ->on('remision_cabecera')
                ->onDelete('cascade');

            $table->foreign('articulo_id')
                ->references('cod_articulo')
                ->on('articulos')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remision_detalle');
        Schema::dropIfExists('remision_cabecera');
    }
};
