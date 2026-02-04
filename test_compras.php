<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompraCabecera;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\Articulos;
use Illuminate\Support\Facades\DB;

echo "=== TEST SISTEMA DE COMPRAS ===\n\n";

// 1. Verificar tablas
echo "1. Verificando estructura de tablas...\n";
try {
    $tablesCabecera = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'cm_compras_cabecera' ORDER BY ordinal_position");
    echo "   ✓ cm_compras_cabecera: " . count($tablesCabecera) . " columnas\n";

    $tablesDetalle = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'cm_compras_detalle' ORDER BY ordinal_position");
    echo "   ✓ cm_compras_detalle: " . count($tablesDetalle) . " columnas\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Verificando relaciones del modelo...\n";
$compra = new CompraCabecera();
echo "   ✓ Table: {$compra->getTable()}\n";
echo "   ✓ Primary Key: {$compra->getKeyName()}\n";
echo "   ✓ Fillable: " . count($compra->getFillable()) . " campos\n";

$detalle = new CompraDetalle();
echo "   ✓ Detalle Table: {$detalle->getTable()}\n";
echo "   ✓ Detalle Primary Key: {$detalle->getKeyName()}\n";

echo "\n3. Verificando datos existentes...\n";
$proveedores = Proveedor::count();
echo "   Proveedores: {$proveedores}\n";

$articulos = Articulos::count();
echo "   Artículos: {$articulos}\n";

$compras = CompraCabecera::count();
echo "   Compras: {$compras}\n";

if ($compras > 0) {
    echo "\n4. Mostrando última compra:\n";
    $ultima = CompraCabecera::with(['proveedor', 'detalles.articulo'])->latest('id_compra_cabecera')->first();
    if ($ultima) {
        echo "   ID: {$ultima->id_compra_cabecera}\n";
        $nombreProveedor = $ultima->proveedor->nombre ?? 'N/A';
        echo "   Proveedor: {$nombreProveedor}\n";
        echo "   Número: {$ultima->numero_completo}\n";
        echo "   Fecha: {$ultima->fec_comprobante->format('d/m/Y')}\n";
        echo "   Total: " . number_format($ultima->total_compra, 0, ',', '.') . " Gs\n";
        echo "   Detalles: {$ultima->detalles->count()} items\n";

        if ($ultima->detalles->count() > 0) {
            echo "\n   Items:\n";
            foreach ($ultima->detalles as $det) {
                $descArticulo = $det->articulo->descripcion ?? 'N/A';
                echo "   - {$descArticulo}: {$det->cantidad} x " . number_format($det->precio_unitario, 0, ',', '.') . " Gs\n";
            }
        }
    }
}echo "\n=== TEST COMPLETADO ===\n";
