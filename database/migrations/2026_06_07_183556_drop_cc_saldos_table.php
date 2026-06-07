<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cc_saldos');
    }

    public function down(): void
    {
        Schema::create('cc_saldos', function ($table) {
            $table->bigIncrements('cod_saldo');
            $table->bigInteger('cod_cliente');
            $table->string('tipo_comprobante', 50);
            $table->string('nro_comprobante', 100);
            $table->date('fecha_comprobante');
            $table->decimal('debe', 12, 2)->default(0);
            $table->decimal('haber', 12, 2)->default(0);
            $table->decimal('saldo_actual', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->bigInteger('cod_factura')->nullable();
            $table->bigInteger('usuario_alta')->nullable();
            $table->timestamp('fecha_alta')->nullable();
            $table->timestamp('fec_alta')->nullable();
        });
    }
};
