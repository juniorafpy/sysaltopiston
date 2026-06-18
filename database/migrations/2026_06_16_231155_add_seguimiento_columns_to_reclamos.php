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
        Schema::table('reclamos', function (Blueprint $table) {
            if (!Schema::hasColumn('reclamos', 'estado')) {
                $table->enum('estado', ['Pendiente', 'En Proceso', 'Resuelto', 'Rechazado', 'Cerrado'])
                    ->default('Pendiente')
                    ->after('descripcion');
            }

            if (!Schema::hasColumn('reclamos', 'responsable')) {
                $table->string('responsable', 100)->nullable()->after('estado');
            }

            if (!Schema::hasColumn('reclamos', 'fecha_resolucion')) {
                $table->date('fecha_resolucion')->nullable()->after('responsable');
            }

            if (!Schema::hasColumn('reclamos', 'accion_tomada')) {
                $table->text('accion_tomada')->nullable()->after('fecha_resolucion');
            }

            if (!Schema::hasColumn('reclamos', 'resolucion')) {
                $table->text('resolucion')->nullable()->after('accion_tomada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'responsable', 'fecha_resolucion', 'accion_tomada', 'resolucion']);
        });
    }
};
