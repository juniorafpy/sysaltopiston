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
        Schema::table('articulos', function (Blueprint $table) {
            // Categoría del repuesto
            $table->unsignedBigInteger('cod_tipo_repuesto')->nullable()->after('cod_tip_articulo');
            $table->foreign('cod_tipo_repuesto')->references('cod_tipo_repuesto')->on('tipo_repuesto')->onDelete('set null');

            // Campos adicionales útiles para repuestos
            $table->string('codigo_oem', 50)->nullable()->after('descripcion')->comment('Código OEM del fabricante');
            $table->string('codigo_barras', 50)->nullable()->after('codigo_oem')->comment('Código de barras EAN/UPC');
            $table->string('ubicacion', 100)->nullable()->after('cod_medida')->comment('Ubicación física en almacén');
            $table->integer('stock_minimo')->default(0)->after('ubicacion')->comment('Stock mínimo para alertas');
            $table->integer('stock_maximo')->default(0)->after('stock_minimo')->comment('Stock máximo sugerido');
            $table->decimal('peso', 10, 3)->nullable()->after('stock_maximo')->comment('Peso en kg');
            $table->text('notas')->nullable()->after('precio')->comment('Observaciones y notas adicionales');
            $table->string('garantia', 50)->nullable()->after('notas')->comment('Tiempo de garantía (ej: 6 meses, 1 año)');
            $table->boolean('es_importado')->default(false)->after('garantia')->comment('Indica si es producto importado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropForeign(['cod_tipo_repuesto']);
            $table->dropColumn([
                'cod_tipo_repuesto',
                'codigo_oem',
                'codigo_barras',
                'ubicacion',
                'stock_minimo',
                'stock_maximo',
                'peso',
                'notas',
                'garantia',
                'es_importado',
            ]);
        });
    }
};
