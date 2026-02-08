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
        Schema::create('motivos_nota_credito_debito', function (Blueprint $table) {
            $table->id('cod_motivo');
            $table->string('tipo_nota', 2); // 'NC' o 'ND'
            $table->string('descripcion', 100);
            $table->boolean('afecta_stock')->default(false); // Si devuelve mercadería físicamente
            $table->boolean('afecta_saldo')->default(true); // Si ajusta el saldo de la factura
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar motivos predefinidos para Notas de Crédito
        DB::table('motivos_nota_credito_debito')->insert([
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Devolución de mercadería',
                'afecta_stock' => true,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Descuento comercial',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Error en precio facturado',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Mercadería dañada o vencida',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Error en cantidad facturada',
                'afecta_stock' => true,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'NC',
                'descripcion' => 'Bonificación posterior',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Motivos para Notas de Débito
            [
                'tipo_nota' => 'ND',
                'descripcion' => 'Intereses por mora',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'ND',
                'descripcion' => 'Gastos de envío adicionales',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'ND',
                'descripcion' => 'Error en precio (menor al real)',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nota' => 'ND',
                'descripcion' => 'Ajuste de precio por diferencia cambiaria',
                'afecta_stock' => false,
                'afecta_saldo' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motivos_nota_credito_debito');
    }
};
