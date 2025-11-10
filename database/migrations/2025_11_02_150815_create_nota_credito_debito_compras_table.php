<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_debito_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_cabecera_id')->constrained('cm_compras_cabecera', 'id_compra_cabecera');
            $table->enum('tipo_nota', ['credito', 'debito']);
            $table->date('fecha');
            $table->text('motivo')->nullable();
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_debito_compras');
    }
};
