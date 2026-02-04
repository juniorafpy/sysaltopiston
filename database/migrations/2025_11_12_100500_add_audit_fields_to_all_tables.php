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
        $tables = [
            'orden_compra_detalle',
            'tipos_articulos',
            'rucs',
            'cm_compras_detalle',
            'orden_compra_cabecera',
            'ciudad',
            'impuestos',
            'tipo_repuesto',
            'cargos',
            'password_reset_tokens',
            'estado_civil',
            'personas',
            'marcas',
            'st_modelos',
            'empleados',
            'recepcion_vehiculos',
            'condicion_compra',
            'cm_compras_cabecera',
            'nota_credito_debito_compras',
            'guia_remision_cabecera',
            'guia_remision_detalle',
            'diagnosticos',
            'presupuesto_venta_detalles',
            'recepcion_inventarios',
            'orden_servicios',
            'existe_stock',
            'entidades_bancarias',
            'orden_servicio_detalles',
            'tipo_reclamos',
            'promocion_detalles',
            'reclamos',
            'promociones',
            'cajas',
            'aperturas_caja',
            'facturas',
            'factura_detalles',
            'libro_iva',
            'cc_saldos',
            'timbrados',
            'caja_timbrado',
            'notas',
            'notas_detalle',
            'estados',
            'factura_vencimientos',
            'movimientos_caja',
            'presupuesto_ventas',
            'vehiculos',
            'proveedores',
            'departamentos',
            'pais',
            'personal_access_tokens',
            'failed_jobs',
            'users',
            'almacenes',
            'articulos',
            // add other tables here if needed
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                // Add usuario_alta if not exists
                if (!Schema::hasColumn($table, 'usuario_alta')) {
                    $tableBlueprint->string('usuario_alta')->nullable()->after('usuario_mod');
                }

                // Add fec_alta if not exists
                if (!Schema::hasColumn($table, 'fec_alta')) {
                    $tableBlueprint->timestamp('fec_alta')->nullable()->after('usuario_alta');
                }

                // Remove created_at/updated_at if they exist
                if (Schema::hasColumn($table, 'created_at')) {
                    try {
                        $tableBlueprint->dropColumn('created_at');
                    } catch (\Exception $e) {
                        // ignore drop errors in alter
                    }
                }
                if (Schema::hasColumn($table, 'updated_at')) {
                    try {
                        $tableBlueprint->dropColumn('updated_at');
                    } catch (\Exception $e) {
                        // ignore drop errors in alter
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            // same list
            'orden_compra_detalle',
            'tipos_articulos',
            'rucs',
            'cm_compras_detalle',
            'orden_compra_cabecera',
            'ciudad',
            'impuestos',
            'tipo_repuesto',
            'cargos',
            'password_reset_tokens',
            'estado_civil',
            'personas',
            'marcas',
            'st_modelos',
            'empleados',
            'recepcion_vehiculos',
            'condicion_compra',
            'cm_compras_cabecera',
            'nota_credito_debito_compras',
            'guia_remision_cabecera',
            'guia_remision_detalle',
            'diagnosticos',
            'presupuesto_venta_detalles',
            'recepcion_inventarios',
            'orden_servicios',
            'existe_stock',
            'entidades_bancarias',
            'orden_servicio_detalles',
            'tipo_reclamos',
            'promocion_detalles',
            'reclamos',
            'promociones',
            'cajas',
            'aperturas_caja',
            'facturas',
            'factura_detalles',
            'libro_iva',
            'cc_saldos',
            'timbrados',
            'caja_timbrado',
            'notas',
            'notas_detalle',
            'estados',
            'factura_vencimientos',
            'movimientos_caja',
            'presupuesto_ventas',
            'vehiculos',
            'proveedores',
            'departamentos',
            'pais',
            'personal_access_tokens',
            'failed_jobs',
            'users',
            'almacenes',
            'articulos',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                if (Schema::hasColumn($table, 'usuario_alta')) {
                    try {
                        $tableBlueprint->dropColumn('usuario_alta');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                if (Schema::hasColumn($table, 'fec_alta')) {
                    try {
                        $tableBlueprint->dropColumn('fec_alta');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                // Can't reliably restore created_at/updated_at; skipping
            });
        }
    }
};
