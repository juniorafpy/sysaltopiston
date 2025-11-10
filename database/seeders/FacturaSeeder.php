<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Factura;
use App\Models\Timbrado;
use App\Models\Personas;
use App\Models\Articulos;
use App\Models\PresupuestoVenta;
use Carbon\Carbon;

class FacturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existen facturas
        if (Factura::count() > 0) {
            $this->command->info('❌ Ya existen facturas. Saltando seeder...');
            return;
        }

        $this->command->info('📄 Creando facturas...');

        // Obtener timbrado vigente
        $timbrado = Timbrado::vigentes()->first();

        if (!$timbrado) {
            $this->command->error('❌ No hay timbrados vigentes. Por favor ejecute TimbradoSeeder primero.');
            return;
        }

        // Obtener clientes (personas activas)
        $clientes = Personas::where('ind_activo', 'S')->limit(10)->get();

        if ($clientes->isEmpty()) {
            $this->command->error('❌ No hay clientes disponibles. Por favor ejecute los seeders de personas primero.');
            return;
        }        // Obtener artículos
        $articulos = Articulos::where('activo', true)->limit(20)->get();

        if ($articulos->isEmpty()) {
            $this->command->error('❌ No hay artículos disponibles.');
            return;
        }

        // Obtener presupuestos aprobados (si existen)
        $presupuestos = PresupuestoVenta::where('estado', 'Aprobado')
            ->whereDoesntHave('facturas')
            ->limit(5)
            ->get();

        $facturasCreadas = 0;

        // Crear 5 facturas desde presupuestos (si existen)
        if ($presupuestos->isNotEmpty()) {
            $this->command->info('📋 Creando facturas desde presupuestos...');

            foreach ($presupuestos as $presupuesto) {
                try {
                    // Preparar detalles del presupuesto
                    $detalles = $presupuesto->detalles->map(function ($detalle) {
                        $articulo = $detalle->articulo;
                        $cantidad = $detalle->cantidad;
                        $precioUnitario = $detalle->precio_unitario;
                        $tipoIva = '10'; // Default 10%

                        // Calcular valores
                        $subtotal = $cantidad * $precioUnitario;
                        $montoIva = ($subtotal * 10) / 110;
                        $total = $subtotal;

                        return [
                            'cod_articulo' => $detalle->articulo_id,
                            'descripcion' => $articulo->descripcion ?? 'Artículo',
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'porcentaje_descuento' => 0,
                            'monto_descuento' => 0,
                            'subtotal' => $subtotal,
                            'tipo_iva' => $tipoIva,
                            'porcentaje_iva' => 10,
                            'monto_iva' => $montoIva,
                            'total' => $total,
                        ];
                    })->toArray();

                    // Calcular totales
                    $subtotalGravado10 = collect($detalles)->sum('subtotal');
                    $totalIva10 = collect($detalles)->sum('monto_iva');
                    $totalGeneral = $subtotalGravado10;

                    // Generar factura
                    Factura::generarFactura([
                        'cod_timbrado' => $timbrado->cod_timbrado,
                        'fecha_factura' => Carbon::now()->subDays(rand(1, 30)),
                        'cod_cliente' => $presupuesto->cliente_id,
                        'condicion_venta' => rand(0, 1) ? 'Contado' : 'Crédito',
                        'presupuesto_venta_id' => $presupuesto->id,
                        'subtotal_gravado_10' => $subtotalGravado10,
                        'subtotal_gravado_5' => 0,
                        'subtotal_exenta' => 0,
                        'total_iva_10' => $totalIva10,
                        'total_iva_5' => 0,
                        'total_general' => $totalGeneral,
                        'detalles' => $detalles,
                    ]);

                    $facturasCreadas++;
                } catch (\Exception $e) {
                    $this->command->warn("⚠️ Error al crear factura desde presupuesto #{$presupuesto->id}: " . $e->getMessage());
                }
            }
        }

        // Crear 15 facturas directas (sin presupuesto)
        $this->command->info('📝 Creando facturas directas...');

        for ($i = 0; $i < 15; $i++) {
            try {
                $cliente = $clientes->random();
                $cantidadItems = rand(1, 5);
                $detalles = [];

                // Generar detalles aleatorios
                for ($j = 0; $j < $cantidadItems; $j++) {
                    $articulo = $articulos->random();
                    $cantidad = rand(1, 5);
                    $precioUnitario = $articulo->precio_venta ?? rand(10000, 500000);
                    $porcentajeDescuento = rand(0, 20);
                    $tipoIva = ['10', '5', 'Exenta'][array_rand(['10', '5', 'Exenta'])];

                    // Calcular valores
                    $importeBruto = $cantidad * $precioUnitario;
                    $montoDescuento = ($importeBruto * $porcentajeDescuento) / 100;
                    $subtotal = $importeBruto - $montoDescuento;

                    $porcentajeIva = match ($tipoIva) {
                        '10' => 10,
                        '5' => 5,
                        default => 0,
                    };

                    $montoIva = match ($tipoIva) {
                        '10' => ($subtotal * 10) / 110,
                        '5' => ($subtotal * 5) / 105,
                        default => 0,
                    };

                    $total = $subtotal;

                    $detalles[] = [
                        'cod_articulo' => $articulo->cod_articulo,
                        'descripcion' => $articulo->descripcion ?? 'Artículo',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'porcentaje_descuento' => $porcentajeDescuento,
                        'monto_descuento' => $montoDescuento,
                        'subtotal' => $subtotal,
                        'tipo_iva' => $tipoIva,
                        'porcentaje_iva' => $porcentajeIva,
                        'monto_iva' => $montoIva,
                        'total' => $total,
                    ];
                }

                // Calcular totales por tipo de IVA
                $subtotalGravado10 = collect($detalles)->where('tipo_iva', '10')->sum('subtotal');
                $totalIva10 = collect($detalles)->where('tipo_iva', '10')->sum('monto_iva');
                $subtotalGravado5 = collect($detalles)->where('tipo_iva', '5')->sum('subtotal');
                $totalIva5 = collect($detalles)->where('tipo_iva', '5')->sum('monto_iva');
                $subtotalExenta = collect($detalles)->where('tipo_iva', 'Exenta')->sum('subtotal');
                $totalGeneral = $subtotalGravado10 + $subtotalGravado5 + $subtotalExenta;

                // Generar factura
                Factura::generarFactura([
                    'cod_timbrado' => $timbrado->cod_timbrado,
                    'fecha_factura' => Carbon::now()->subDays(rand(1, 60)),
                    'cod_cliente' => $cliente->cod_persona,
                    'condicion_venta' => rand(0, 2) ? 'Contado' : 'Crédito', // 66% Contado, 33% Crédito
                    'subtotal_gravado_10' => $subtotalGravado10,
                    'subtotal_gravado_5' => $subtotalGravado5,
                    'subtotal_exenta' => $subtotalExenta,
                    'total_iva_10' => $totalIva10,
                    'total_iva_5' => $totalIva5,
                    'total_general' => $totalGeneral,
                    'observaciones' => rand(0, 3) ? null : 'Observación de prueba',
                    'detalles' => $detalles,
                ]);

                $facturasCreadas++;
            } catch (\Exception $e) {
                $this->command->warn("⚠️ Error al crear factura directa: " . $e->getMessage());
            }
        }

        // Resumen de facturas creadas
        $this->command->info('');
        $this->command->table(
            ['Total', 'Contado', 'Crédito', 'Desde Presup.', 'Directas'],
            [[
                Factura::count(),
                Factura::where('condicion_venta', 'Contado')->count(),
                Factura::where('condicion_venta', 'Crédito')->count(),
                Factura::whereNotNull('presupuesto_venta_id')->count(),
                Factura::whereNull('presupuesto_venta_id')->count(),
            ]]
        );

        $this->command->info('');
        $this->command->info("✅ {$facturasCreadas} facturas creadas exitosamente.");
        $this->command->info("📚 Libro IVA: " . \App\Models\LibroIva::count() . " registros");
        $this->command->info("💰 CC Saldos: " . \App\Models\CCSaldo::count() . " registros");
    }
}
