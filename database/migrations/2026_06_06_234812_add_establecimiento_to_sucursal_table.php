<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursal', function (Blueprint $table) {
            if (!Schema::hasColumn('sucursal', 'establecimiento')) {
                $table->string('establecimiento', 3)->nullable()->after('descripcion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sucursal', function (Blueprint $table) {
            $table->dropColumn('establecimiento');
        });
    }
};
