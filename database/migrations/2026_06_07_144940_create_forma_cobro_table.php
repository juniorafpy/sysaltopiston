<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forma_cobro', function (Blueprint $table) {
            $table->tinyInteger('cod_forma_cobro')->primary();
            $table->string('descripcion', 50);
        });

        DB::table('forma_cobro')->insert([
            ['cod_forma_cobro' => 1, 'descripcion' => 'Efectivo'],
            ['cod_forma_cobro' => 2, 'descripcion' => 'Tarjeta de Crédito'],
            ['cod_forma_cobro' => 3, 'descripcion' => 'Tarjeta de Débito'],
            ['cod_forma_cobro' => 4, 'descripcion' => 'Transferencia'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('forma_cobro');
    }
};
