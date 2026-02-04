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
        // Agregar campos de auditoría a cm_compras_cabecera
        Schema::table('cm_compras_cabecera', function (Blueprint $table) {
            $table->string('usuario_alta', 100)->nullable()->after('observacion');
            $table->timestamp('fecha_alta')->nullable()->after('usuario_alta');
            $table->string('usuario_mod', 100)->nullable()->after('fecha_alta');
            $table->timestamp('fecha_mod')->nullable()->after('usuario_mod');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_compras_cabecera', function (Blueprint $table) {
            $table->dropColumn([
                'usuario_alta',
                'fecha_alta',
                'usuario_mod',
                'fecha_mod',
                'created_at',
                'updated_at'
            ]);
        });
    }
};
