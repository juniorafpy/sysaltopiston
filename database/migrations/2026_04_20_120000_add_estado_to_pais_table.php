<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pais', function (Blueprint $table) {
            $table->string('estado', 1)->default('S')->after('abreviatura');
        });
    }

    public function down(): void
    {
        Schema::table('pais', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
