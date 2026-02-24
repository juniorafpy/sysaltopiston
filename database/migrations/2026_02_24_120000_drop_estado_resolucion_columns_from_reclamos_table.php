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
        if (Schema::hasColumn('reclamos', 'estado')) {
            Schema::table('reclamos', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }

        if (Schema::hasColumn('reclamos', 'resolucion')) {
            Schema::table('reclamos', function (Blueprint $table) {
                $table->dropColumn('resolucion');
            });
        }

        if (Schema::hasColumn('reclamos', 'fecha_resolucion')) {
            Schema::table('reclamos', function (Blueprint $table) {
                $table->dropColumn('fecha_resolucion');
            });
        }

        if (Schema::hasColumn('reclamos', 'usuario_resolucion')) {
            Schema::table('reclamos', function (Blueprint $table) {
                $table->dropColumn('usuario_resolucion');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            if (!Schema::hasColumn('reclamos', 'estado')) {
                $table->enum('estado', ['Pendiente', 'En Proceso', 'Resuelto', 'Cerrado'])
                    ->default('Pendiente');
            }

            if (!Schema::hasColumn('reclamos', 'resolucion')) {
                $table->text('resolucion')->nullable();
            }

            if (!Schema::hasColumn('reclamos', 'fecha_resolucion')) {
                $table->date('fecha_resolucion')->nullable();
            }

            if (!Schema::hasColumn('reclamos', 'usuario_resolucion')) {
                $table->unsignedBigInteger('usuario_resolucion')->nullable();
            }
        });
    }
};
