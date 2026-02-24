<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('presupuesto_ventas', 'cliente_nombre')) {
                $table->string('cliente_nombre', 255)->nullable()->after('cliente_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_ventas', function (Blueprint $table) {
            if (Schema::hasColumn('presupuesto_ventas', 'cliente_nombre')) {
                $table->dropColumn('cliente_nombre');
            }
        });
    }
};
