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
        Schema::table('orden_servicio_detalles', function (Blueprint $table) {
            $table->decimal('cantidad_real', 12, 2)->default(0)->after('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_servicio_detalles', function (Blueprint $table) {
            $table->dropColumn('cantidad_real');
        });
    }
};
