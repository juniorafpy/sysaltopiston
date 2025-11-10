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
        Schema::create('orden_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_venta_id')->constrained('presupuesto_ventas')->onDelete('restrict');
            $table->foreignId('diagnostico_id')->nullable()->constrained('diagnosticos')->onDelete('restrict');
            $table->foreignId('recepcion_vehiculo_id')->nullable()->constrained('recepcion_vehiculos')->onDelete('restrict');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('cod_sucursal')->nullable();

            // Campos de fechas y seguimiento
            $table->date('fecha_inicio');
            $table->date('fecha_estimada_finalizacion')->nullable();
            $table->date('fecha_finalizacion_real')->nullable();

            // Estado del trabajo
            $table->enum('estado_trabajo', [
                'Pendiente',
                'En Proceso',
                'Pausado',
                'Finalizado',
                'Cancelado',
                'Facturado'
            ])->default('Pendiente');

            // Personal asignado
            $table->unsignedBigInteger('mecanico_asignado_id')->nullable();

            // Observaciones
            $table->text('observaciones_tecnicas')->nullable();
            $table->text('observaciones_internas')->nullable();

            // Totales
            $table->decimal('total', 15, 2)->default(0);

            // Campos de sistema
            $table->string('usuario_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_mod')->nullable();
            $table->timestamp('fec_mod')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cliente_id')->references('cod_persona')->on('personas');
            $table->foreign('cod_sucursal')->references('cod_sucursal')->on('sucursal')->onDelete('set null');
            $table->foreign('mecanico_asignado_id')->references('cod_empleado')->on('empleados')->onDelete('set null');

            // Índices
            $table->index('estado_trabajo');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_servicios');
    }
};
