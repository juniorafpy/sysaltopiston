<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id('cod_impuesto');
            $table->string('descripcion', 100);
            $table->decimal('porcentaje', 5, 2); // Ejemplo: 10.00 para IVA 10%
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar datos iniciales de IVA en Paraguay
        DB::table('impuestos')->insert([
            ['descripcion' => 'IVA 10%', 'porcentaje' => 10.00, 'activo' => true],
            ['descripcion' => 'IVA 5%', 'porcentaje' => 5.00, 'activo' => true],
            ['descripcion' => 'Exenta', 'porcentaje' => 0.00, 'activo' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
