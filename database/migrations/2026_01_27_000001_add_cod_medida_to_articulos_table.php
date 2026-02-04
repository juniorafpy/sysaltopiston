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
            $table->unsignedBigInteger('cod_medida')->nullable()->after('precio');
            $table->foreign('cod_medida')->references('cod_medida')->on('medidas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropForeign(['cod_medida']);
            $table->dropColumn('cod_medida');
        });
    }
};
