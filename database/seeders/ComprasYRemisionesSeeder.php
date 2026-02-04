<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComprasYRemisionesSeeder extends Seeder
{
    public function run(): void
    {
        // Primero necesitamos IDs de proveedores, artículos, sucursales y empleados
        $proveedor1 = DB::table('proveedores')->first();
        $empleado1 = DB::table('empleados')->first();
        $sucursal1 = DB::table('sucursales')->first();
        $almacen1 = DB::table('almacenes')->first();
        
        $articulos = DB::table('articulos')->limit(10)->get();
        
        if (!$proveedor1 || !$empleado1 || !$sucursal1 || !$almacen1 || $articulos->count() < 5) {
            $this->command->error('Faltan datos base: proveedores, empleados, sucursales, almacenes o artículos');
            return;
        }

        // Crear 6 Compras con sus detalles y guías de remisión
        for ($i = 1; $i <= 6; $i++) {
            // 1. Crear Compra Cabecera
            $compraCabecera = DB::table('cm_compras_cabecera')->insertGetId([
                'cod_proveedor' => $proveedor1->cod_proveedor,
                'cod_condicion_compra' => 1,
                'cod_sucursal' => $sucursal1->cod_sucursal,
                'fec_comprobante' => Carbon::now()->subDays(10 - $i)->toDateString(),
                'tip_comprobante' => 'FAC',
                'ser_comprobante' => '001',
                'timbrado' => '12345678',
                'nro_comprobante' => str_pad($i, 7, '0', STR_PAD_LEFT),
                'observacion' => "Compra de repuestos - Lote #{$i}",
                'usuario_alta' => 'sistema',
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Crear Detalles de Compra (3-4 artículos por compra)
            $numArticulos = rand(3, 4);
            $articulosSeleccionados = $articulos->random($numArticulos);
            
            foreach ($articulosSeleccionados as $articulo) {
                DB::table('cm_compras_detalle')->insert([
                    'id_compra_cabecera' => $compraCabecera,
                    'cod_articulo' => $articulo->cod_articulo,
                    'cantidad' => rand(5, 50),
                    'precio_unitario' => rand(50000, 500000),
                    'porcentaje_iva' => 10,
                    'monto_total_linea' => rand(250000, 25000000),
                ]);
            }

            // 3. Crear Guía de Remisión Cabecera
            $guiaCabecera = DB::table('guia_remision_cabecera')->insertGetId([
                'compra_cabecera_id' => $compraCabecera,
                'almacen_id' => $almacen1->cod_almacen,
                'tipo_comprobante' => 'REM',
                'ser_remision' => '001',
                'numero_remision' => str_pad($i, 7, '0', STR_PAD_LEFT),
                'fecha_remision' => Carbon::now()->subDays(9 - $i)->toDateString(),
                'cod_empleado' => $empleado1->cod_empleado,
                'cod_sucursal' => $sucursal1->cod_sucursal,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
                'estado' => 'P',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Crear Guía de Remisión Detalle (mismos artículos que la compra)
            foreach ($articulosSeleccionados as $articulo) {
                DB::table('guia_remision_detalle')->insert([
                    'guia_remision_cabecera_id' => $guiaCabecera,
                    'articulo_id' => $articulo->cod_articulo,
                    'cantidad_recibida' => rand(5, 50),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Se crearon 6 compras con sus respectivas guías de remisión');
    }
}
