<?php

namespace Database\Seeders;

use App\Models\Articulos;
use App\Models\CondicionCompra;
use App\Models\Diagnostico;
use App\Models\Medidas;
use App\Models\PresupuestoVenta;
use App\Models\RecepcionVehiculo;
use App\Models\TipoArticulos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PresupuestoVentaSeeder extends Seeder
{
    public function run(): void
    {
        if (! RecepcionVehiculo::exists()) {
            $this->call(RecepcionVehiculoSeeder::class);
        }

        $recepcion = RecepcionVehiculo::with(['cliente', 'vehiculo'])->first();

        if (! $recepcion) {
            return;
        }

        if (! CondicionCompra::exists()) {
            $this->call(CondicionCompraSeeder::class);
        }

        $condicion = CondicionCompra::first();

        $medidaId = null;
        if (Schema::hasTable('medidas')) {
            $medidaId = Medidas::firstOrCreate(['descripcion' => 'Unidad'])->cod_medida ?? null;
        }

        $tipoArticuloId = null;
        if (Schema::hasTable('tipos_articulos')) {
            $tipoArticuloId = TipoArticulos::firstOrCreate(['descripcion' => 'Repuesto mecánico'])->cod_tip_articulo ?? null;
        }

        $articulosData = [
            ['descripcion' => 'Juego de pastillas de freno', 'precio' => 450000, 'costo' => 300000],
            ['descripcion' => 'Kit de mantenimiento preventivo', 'precio' => 350000, 'costo' => 200000],
            ['descripcion' => 'Mano de obra especializada', 'precio' => 250000, 'costo' => 0],
        ];

        $articulos = new Collection();

        $articulosTableExists = Schema::hasTable('articulos');

        $articuloColumns = collect($articulosTableExists ? Schema::getColumnListing('articulos') : [])
            ->map(fn ($column) => strtolower($column))
            ->all();

        $hasArticuloColumn = static fn (array $columns, string $name): bool => in_array(strtolower($name), $columns, true);

        foreach ($articulosData as $item) {
            if (! $articulosTableExists) {
                break;
            }

            $defaults = [];

            if ($hasArticuloColumn($articuloColumns, 'cod_marca')) {
                $defaults['cod_marca'] = $recepcion->vehiculo?->marca_id;
            }

            if ($hasArticuloColumn($articuloColumns, 'cod_modelo')) {
                $defaults['cod_modelo'] = $recepcion->vehiculo?->modelo_id;
            }

            if ($hasArticuloColumn($articuloColumns, 'precio')) {
                $defaults['precio'] = $item['precio'];
            } elseif ($hasArticuloColumn($articuloColumns, 'precio_unitario')) {
                $defaults['precio_unitario'] = $item['precio'];
            }

            if ($hasArticuloColumn($articuloColumns, 'cod_medida')) {
                $defaults['cod_medida'] = $medidaId;
            }

            if ($hasArticuloColumn($articuloColumns, 'cod_tip_articulo')) {
                $defaults['cod_tip_articulo'] = $tipoArticuloId;
            }

            if ($hasArticuloColumn($articuloColumns, 'activo')) {
                $defaults['activo'] = true;
            }

            if ($hasArticuloColumn($articuloColumns, 'costo')) {
                $defaults['costo'] = $item['costo'];
            }

            if ($hasArticuloColumn($articuloColumns, 'usuario_alta')) {
                $defaults['usuario_alta'] = 'seeder';
            }

            if ($hasArticuloColumn($articuloColumns, 'fec_alta')) {
                $defaults['fec_alta'] = now();
            }

            $articulo = Articulos::firstOrCreate(
                ['descripcion' => $item['descripcion']],
                $defaults
            );

            $articulos->push([
                'model' => $articulo,
                'precio' => $item['precio'],
            ]);
        }

        $diagnostico = Diagnostico::firstOrCreate(
            ['recepcion_vehiculo_id' => $recepcion->id],
            [
                'empleado_id' => $recepcion->empleado_id,
                'fecha_diagnostico' => now()->subDay(),
                'diagnostico_mecanico' => 'Se detectó desgaste pronunciado en el sistema de frenos y se recomienda mantenimiento completo.',
                'observaciones' => 'Generado automáticamente para pruebas de presupuesto.',
                'estado' => 'Pendiente a presupuesto',
                'usuario_alta' => 'seeder',
                'fec_alta' => now(),
            ]
        );

        $presupuesto = PresupuestoVenta::updateOrCreate(
            ['diagnostico_id' => $diagnostico->id],
            [
                'cliente_id' => $recepcion->cliente_id,
                'recepcion_vehiculo_id' => $recepcion->id,
                'fecha_presupuesto' => now(),
                'estado' => 'Pendiente',
                'observaciones' => 'Presupuesto de venta de ejemplo vinculado al diagnóstico generado por seeders.',
                'cod_condicion_compra' => $condicion?->cod_condicion_compra,
                'total' => 0,
            ]
        );

        $presupuesto->detalles()->delete();

        $subtotalGeneral = 0;
        $impuestosTotales = 0;
        $totalGeneral = 0;

        $lineas = [
            ['descripcion' => 'Juego de pastillas de freno', 'cantidad' => 1, 'porcentaje' => 10],
            ['descripcion' => 'Kit de mantenimiento preventivo', 'cantidad' => 1, 'porcentaje' => 10],
            ['descripcion' => 'Mano de obra especializada', 'cantidad' => 2, 'porcentaje' => 10],
        ];

        foreach ($lineas as $linea) {
            $articuloData = $articulos->first(function ($item) use ($linea) {
                return $item['model']->descripcion === $linea['descripcion'];
            });

            if (! $articuloData) {
                continue;
            }

            /** @var \App\Models\Articulos $articulo */
            $articulo = $articuloData['model'];

            $cantidad = (float) $linea['cantidad'];
            $precio = (float) ($articuloData['precio'] ?? $articulo->precio ?? 0);
            $porcentaje = (float) $linea['porcentaje'];

            $subtotal = round($cantidad * $precio, 2);
            $impuesto = round($subtotal * ($porcentaje / 100), 2);
            $total = round($subtotal + $impuesto, 2);

            $presupuesto->detalles()->create([
                'cod_articulo' => $articulo->cod_articulo,
                'descripcion' => $articulo->descripcion,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'porcentaje_impuesto' => $porcentaje,
                'monto_impuesto' => $impuesto,
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            $subtotalGeneral += $subtotal;
            $impuestosTotales += $impuesto;
            $totalGeneral += $total;
        }

        $presupuesto->update(['total' => round($totalGeneral, 2)]);
    }
}
