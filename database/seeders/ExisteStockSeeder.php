<?php

namespace Database\Seeders;

use App\Models\Articulos;
use App\Models\ExisteStock;
use App\Models\Sucursal;
use App\Models\PresupuestoVentaDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExisteStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las sucursales
        $sucursales = Sucursal::all();

        if ($sucursales->isEmpty()) {
            $this->command->warn('⚠️  No hay sucursales en la base de datos. Creando sucursal por defecto...');

            // Crear sucursal por defecto si no existe
            $sucursal = Sucursal::create([
                'descripcion' => 'Sucursal Central',
            ]);

            $sucursales = collect([$sucursal]);
        }

        // Obtener todos los artículos que están en presupuestos
        $articulosEnPresupuestos = PresupuestoVentaDetalle::select('cod_articulo')
            ->distinct()
            ->pluck('cod_articulo');

        if ($articulosEnPresupuestos->isEmpty()) {
            $this->command->warn('⚠️  No hay artículos en presupuestos. Cargando todos los artículos...');
            $articulosEnPresupuestos = Articulos::pluck('cod_articulo');
        }

        $articulos = Articulos::whereIn('cod_articulo', $articulosEnPresupuestos)->get();

        if ($articulos->isEmpty()) {
            $this->command->error('❌ No hay artículos para agregar stock.');
            return;
        }

        $this->command->info('📦 Agregando stock inicial para ' . $articulos->count() . ' artículos en ' . $sucursales->count() . ' sucursal(es)...');

        $stockCreado = 0;

        foreach ($sucursales as $sucursal) {
            foreach ($articulos as $articulo) {
                // Verificar si ya existe el registro
                $existeStock = ExisteStock::where('cod_articulo', $articulo->cod_articulo)
                    ->where('cod_sucursal', $sucursal->cod_sucursal)
                    ->first();

                if ($existeStock) {
                    $this->command->info("  ⏭️  Stock ya existe para {$articulo->descripcion} en {$sucursal->descripcion}");
                    continue;
                }

                // Determinar stock inicial basado en el tipo de artículo
                $stockInicial = $this->getStockInicialPorArticulo($articulo);
                $stockMinimo = $this->getStockMinimoPorArticulo($articulo);

                ExisteStock::create([
                    'cod_articulo' => $articulo->cod_articulo,
                    'cod_sucursal' => $sucursal->cod_sucursal,
                    'stock_actual' => $stockInicial,
                    'stock_reservado' => 0,
                    'stock_minimo' => $stockMinimo,
                    'usuario_alta' => 'Sistema Seeder',
                    'fec_alta' => now(),
                ]);

                $stockCreado++;

                $this->command->info("  ✅ {$articulo->descripcion} → Stock: {$stockInicial} | Mínimo: {$stockMinimo} | Sucursal: {$sucursal->descripcion}");
            }
        }

        $this->command->info("🎉 Se crearon {$stockCreado} registros de stock exitosamente.");
    }

    /**
     * Determina el stock inicial basado en características del artículo
     */
    private function getStockInicialPorArticulo(Articulos $articulo): float
    {
        $descripcion = strtolower($articulo->descripcion);

        // Artículos de alta rotación
        if (
            str_contains($descripcion, 'filtro') ||
            str_contains($descripcion, 'aceite') ||
            str_contains($descripcion, 'pastilla') ||
            str_contains($descripcion, 'bujia') ||
            str_contains($descripcion, 'correa')
        ) {
            return rand(50, 200); // Stock alto
        }

        // Artículos de media rotación
        if (
            str_contains($descripcion, 'kit') ||
            str_contains($descripcion, 'juego') ||
            str_contains($descripcion, 'repuesto')
        ) {
            return rand(20, 80); // Stock medio
        }

        // Servicios o mano de obra (no necesitan stock físico pero ponemos simbólico)
        if (
            str_contains($descripcion, 'mano de obra') ||
            str_contains($descripcion, 'servicio') ||
            str_contains($descripcion, 'diagnostico')
        ) {
            return 999; // Stock ilimitado simbólico
        }

        // Artículos de baja rotación o especiales
        return rand(10, 50); // Stock bajo
    }

    /**
     * Determina el stock mínimo basado en el stock inicial
     */
    private function getStockMinimoPorArticulo(Articulos $articulo): float
    {
        $stockInicial = $this->getStockInicialPorArticulo($articulo);

        // El stock mínimo es aproximadamente 20% del stock inicial
        if ($stockInicial >= 999) {
            return 999; // Para servicios sin límite
        }

        return max(5, round($stockInicial * 0.2)); // Mínimo 5 unidades
    }
}
