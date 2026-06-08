<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrega_vehiculos', function (Blueprint $table) {
            $table->boolean('recibe_titular')->default(false)->after('observaciones');
            $table->text('firma')->nullable()->after('recibe_titular');
        });
    }

    public function down(): void
    {
        Schema::table('entrega_vehiculos', function (Blueprint $table) {
            $table->dropColumn(['recibe_titular', 'firma']);
        });
    }
};
