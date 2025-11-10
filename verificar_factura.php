<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Obtener la última factura
$factura = App\Models\Factura::with('detalles')->latest('cod_factura')->first();

if (!$factura) {
    echo "No hay facturas en la base de datos\n";
    exit;
}

echo "=== FACTURA {$factura->numero_factura} ===\n";
echo "Cod Factura: {$factura->cod_factura}\n";
echo "Total General: {$factura->total_general}\n";
echo "Subtotal Gravado 10: {$factura->subtotal_gravado_10}\n";
echo "Total IVA 10: {$factura->total_iva_10}\n";
echo "Subtotal Gravado 5: {$factura->subtotal_gravado_5}\n";
echo "Total IVA 5: {$factura->total_iva_5}\n";
echo "Subtotal Exenta: {$factura->subtotal_exenta}\n\n";

echo "=== DETALLES ({$factura->detalles->count()}) ===\n";
foreach ($factura->detalles as $detalle) {
    echo "  Descripción: {$detalle->descripcion}\n";
    echo "  Cantidad: {$detalle->cantidad}\n";
    echo "  Precio Unitario: {$detalle->precio_unitario}\n";
    echo "  Subtotal: {$detalle->subtotal}\n";
    echo "  Tipo IVA: {$detalle->tipo_iva}\n";
    echo "  Monto IVA: {$detalle->monto_iva}\n";
    echo "  Total: {$detalle->total}\n";
    echo "  ---\n";
}

// Recalcular totales manualmente
$totalCalculado = 0;
$subtotalGravado10 = 0;
$totalIva10 = 0;

foreach ($factura->detalles as $detalle) {
    if ($detalle->tipo_iva === '10') {
        $subtotalGravado10 += $detalle->subtotal;
        $totalIva10 += $detalle->monto_iva;
    }
    $totalCalculado += $detalle->subtotal;
}

echo "\n=== RECALCULO MANUAL ===\n";
echo "Subtotal Gravado 10 (calculado): {$subtotalGravado10}\n";
echo "Total IVA 10 (calculado): {$totalIva10}\n";
echo "Total General (calculado): " . ($subtotalGravado10 + $totalIva10) . "\n";
