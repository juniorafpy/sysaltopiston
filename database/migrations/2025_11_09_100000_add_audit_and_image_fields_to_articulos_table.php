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
            if (!Schema::hasColumn('articulos', 'usuario_mod')) {
                $table->string('usuario_mod')->nullable()->after('fec_alta');
            }
            if (!Schema::hasColumn('articulos', 'fec_mod')) {
                $table->timestamp('fec_mod')->nullable()->after('usuario_mod');
            }
            if (!Schema::hasColumn('articulos', 'image')) {
                $table->string('image')->nullable()->after('fec_mod');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn(['usuario_mod', 'fec_mod', 'image']);
        });
    }
};
