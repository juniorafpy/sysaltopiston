<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promocion;
use App\Models\PromocionDetalle;
use App\Models\Articulos;
use Carbon\Carbon;

class PromocionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Promoción vigente actual
        $promocionVigente = Promocion::create([
            'nombre' => 'Descuento Black Friday 2025',
            'descripcion' => 'Descuentos especiales en repuestos seleccionados por Black Friday',
            'fecha_inicio' => Carbon::now()->subDays(5),
            'fecha_fin' => Carbon::now()->addDays(25),
            'activo' => true,
        ]);

        // Obtener algunos artículos para la promoción
        $articulos = Articulos::limit(5)->get();

        if ($articulos->count() > 0) {
            foreach ($articulos as $index => $articulo) {
                PromocionDetalle::create([
                    'promocion_id' => $promocionVigente->id,
                    'articulo_id' => $articulo->cod_articulo,
                    'porcentaje_descuento' => 10 + ($index * 5), // 10%, 15%, 20%, 25%, 30%
                ]);
            }
        }

        // Promoción futura (programada)
        $promocionFutura = Promocion::create([
            'nombre' => 'Promoción Verano 2026',
            'descripcion' => 'Descuentos para la temporada de verano',
            'fecha_inicio' => Carbon::now()->addMonths(2),
            'fecha_fin' => Carbon::now()->addMonths(3),
            'activo' => true,
        ]);

        // Promoción vencida
        $promocionVencida = Promocion::create([
            'nombre' => 'Promoción Halloween 2025',
            'descripcion' => 'Promoción especial de Halloween (ya vencida)',
            'fecha_inicio' => Carbon::now()->subDays(30),
            'fecha_fin' => Carbon::now()->subDays(5),
            'activo' => true,
        ]);

        $articulosVencidos = Articulos::skip(5)->limit(3)->get();
        if ($articulosVencidos->count() > 0) {
            foreach ($articulosVencidos as $articulo) {
                PromocionDetalle::create([
                    'promocion_id' => $promocionVencida->id,
                    'articulo_id' => $articulo->cod_articulo,
                    'porcentaje_descuento' => 20,
                ]);
            }
        }

        // Promoción inactiva
        Promocion::create([
            'nombre' => 'Promoción Desactivada',
            'descripcion' => 'Esta promoción fue desactivada manualmente',
            'fecha_inicio' => Carbon::now()->subDays(10),
            'fecha_fin' => Carbon::now()->addDays(10),
            'activo' => false,
        ]);

        $this->command->info('✓ Promociones creadas exitosamente');
        $this->command->info('  - 1 promoción vigente con ' . $articulos->count() . ' artículos');
        $this->command->info('  - 1 promoción programada (futura)');
        $this->command->info('  - 1 promoción vencida');
        $this->command->info('  - 1 promoción inactiva');
    }
}
