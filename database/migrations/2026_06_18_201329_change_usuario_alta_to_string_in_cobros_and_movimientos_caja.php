<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar llave foránea en cobros (nombre real: fk_cobros_usuario)
        DB::statement('ALTER TABLE cobros DROP CONSTRAINT IF EXISTS fk_cobros_usuario');

        Schema::table('cobros', function (Blueprint $table) {
            $table->string('usuario_alta', 100)->change();
        });

        Schema::table('movimientos_caja', function (Blueprint $table) {
            $table->string('usuario_alta', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_alta')->change();
        });

        Schema::table('cobros', function (Blueprint $table) {
            $table->foreign('usuario_alta')->references('id')->on('users');
        });

        Schema::table('movimientos_caja', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_alta')->nullable()->change();
        });
    }
};
