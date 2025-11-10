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
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id('cod_reclamo');

            // Referencias
            $table->foreignId('cod_cliente')
                ->constrained('personas', 'cod_persona')
                ->onDelete('restrict');

            $table->foreignId('orden_servicio_id')
                ->constrained('orden_servicios')
                ->onDelete('restrict');

            $table->foreignId('cod_tipo_reclamo')
                ->constrained('tipo_reclamos', 'cod_tipo_reclamo')
                ->onDelete('restrict');

            // Datos del reclamo
            $table->date('fecha_reclamo');
            $table->enum('prioridad', ['Alta', 'Media', 'Baja'])->default('Media');
            $table->text('descripcion');
            $table->enum('estado', ['Pendiente', 'En Proceso', 'Resuelto', 'Cerrado'])->default('Pendiente');

            // Resolución (se completa después)
            $table->text('resolucion')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->unsignedBigInteger('usuario_resolucion')->nullable();

            // Auditoría
            // Comentado temporalmente hasta que se cree la tabla sucursales
            // $table->foreignId('cod_sucursal')->nullable()
            //     ->constrained('sucursales', 'cod_sucursal')
            //     ->onDelete('set null');
            $table->unsignedBigInteger('cod_sucursal')->nullable();

            $table->unsignedBigInteger('usuario_alta');
            $table->timestamp('fecha_alta')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
