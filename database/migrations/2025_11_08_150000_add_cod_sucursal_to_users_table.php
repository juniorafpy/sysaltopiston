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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('cod_sucursal')->nullable()->after('email');

            // Foreign key a sucursal
            $table->foreign('cod_sucursal')
                ->references('cod_sucursal')
                ->on('sucursal')
                ->onDelete('set null');

            $table->index('cod_sucursal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cod_sucursal']);
            $table->dropIndex(['cod_sucursal']);
            $table->dropColumn('cod_sucursal');
        });
    }
};
