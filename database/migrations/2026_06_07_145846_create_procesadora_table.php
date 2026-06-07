<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesadora', function (Blueprint $table) {
            $table->tinyInteger('cod_procesadora')->primary();
            $table->string('descripcion', 50);
        });

        DB::table('procesadora')->insert([
            ['cod_procesadora' => 1, 'descripcion' => 'BANCARD'],
            ['cod_procesadora' => 2, 'descripcion' => 'DINELCO'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('procesadora');
    }
};
