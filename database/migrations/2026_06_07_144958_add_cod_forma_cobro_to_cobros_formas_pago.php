<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cobros_formas_pago', function (Blueprint $table) {
            $table->tinyInteger('cod_forma_cobro')->nullable()->after('tipo_transaccion');
        });

        DB::statement("
            UPDATE cobros_formas_pago
            SET cod_forma_cobro = CASE tipo_transaccion
                WHEN 'efectivo' THEN 1
                WHEN 'tarjeta_credito' THEN 2
                WHEN 'tarjeta_debito' THEN 3
                WHEN 'transferencia' THEN 4
            END
        ");
    }

    public function down(): void
    {
        Schema::table('cobros_formas_pago', function (Blueprint $table) {
            $table->dropColumn('cod_forma_cobro');
        });
    }
};
