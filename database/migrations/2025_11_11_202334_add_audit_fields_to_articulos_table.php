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
        Schema::table('articulos', function (Blueprint $table) {
            // Verificar si las columnas no existen antes de agregarlas
            if (!Schema::hasColumn('articulos', 'usuario_alta')) {
                $table->string('usuario_alta', 50)->nullable()->after('activo');
            }
            if (!Schema::hasColumn('articulos', 'fec_alta')) {
                $table->timestamp('fec_alta')->nullable()->after('usuario_alta');
            }
            if (!Schema::hasColumn('articulos', 'usuario_mod')) {
                $table->string('usuario_mod', 50)->nullable()->after('fec_alta');
            }
            if (!Schema::hasColumn('articulos', 'fec_mod')) {
                $table->timestamp('fec_mod')->nullable()->after('usuario_mod');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn(['usuario_alta', 'fec_alta', 'usuario_mod', 'fec_mod']);
        });
    }
};
