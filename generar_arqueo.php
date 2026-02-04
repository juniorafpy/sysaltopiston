<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AperturaCaja;
use App\Models\CobroFormaPago;

// Simple parser de argumentos: --apertura=ID
$aperturaId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--apertura=')) {
        $aperturaId = (int) substr($arg, strlen('--apertura='));
    }
}

if ($aperturaId) {
    $apertura = AperturaCaja::find($aperturaId);
    if (!$apertura) {
        echo "Apertura con ID {$aperturaId} no encontrada.\n";
        exit(1);
    }
} else {
    // Si no se pasa id, tomar la última apertura (mayor cod_apertura)
    $apertura = AperturaCaja::orderByDesc('cod_apertura')->first();
    if (!$apertura) {
        echo "No hay aperturas de caja en el sistema.\n";
        exit(1);
    }
}

echo "Generando arqueo para Apertura ID: {$apertura->cod_apertura}\n";

$montoInicial = $apertura->monto_inicial;
$totalIngresos = $apertura->movimientos()->where('tipo_movimiento', 'Ingreso')->sum('monto');
$totalEgresos = $apertura->movimientos()->where('tipo_movimiento', 'Egreso')->sum('monto');
$saldoEsperado = $apertura->saldo_esperado_calculado;
$efectivoReal = $apertura->efectivo_real;
$diferencia = $apertura->diferencia;

// Breakdown de formas de pago para los cobros asociados a esta apertura
$breakdownFormas = CobroFormaPago::selectRaw('tipo_transaccion, SUM(monto) as total')
    ->join('cobros', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
    ->where('cobros.cod_apertura', $apertura->cod_apertura)
    ->groupBy('tipo_transaccion')
    ->get()
    ->mapWithKeys(function ($row) {
        return [$row->tipo_transaccion => (float) $row->total];
    })
    ->toArray();

// Movimientos detalle (ingresos y egresos)
$movimientos = $apertura->movimientos()->orderBy('fecha_movimiento')->get()->map(function ($m) {
    return [
        'cod_movimiento' => $m->cod_movimiento,
        'tipo_movimiento' => $m->tipo_movimiento,
        'concepto' => $m->concepto,
        'tipo_documento' => $m->tipo_documento,
        'documento_id' => $m->documento_id,
        'monto' => (float) $m->monto,
        'fecha_movimiento' => $m->fecha_movimiento?->toDateTimeString(),
    ];
})->toArray();

$report = [
    'cod_apertura' => $apertura->cod_apertura,
    'fecha_apertura' => $apertura->fecha_apertura?->toDateString(),
    'hora_apertura' => $apertura->hora_apertura,
    'monto_inicial' => (float) $montoInicial,
    'total_ingresos_movimientos' => (float) $totalIngresos,
    'total_egresos_movimientos' => (float) $totalEgresos,
    'saldo_esperado_calculado' => (float) $saldoEsperado,
    'efectivo_real' => $efectivoReal !== null ? (float) $efectivoReal : null,
    'diferencia' => $diferencia !== null ? (float) $diferencia : null,
    'breakdown_formas_pago' => $breakdownFormas,
    'movimientos' => $movimientos,
];

// Imprimir resumen legible
echo "\n--- RESUMEN ---\n";
echo "Monto inicial: " . number_format($report['monto_inicial'], 0, ',', '.') . " Gs\n";
echo "Total ingresos (movimientos): " . number_format($report['total_ingresos_movimientos'], 0, ',', '.') . " Gs\n";
echo "Total egresos (movimientos): " . number_format($report['total_egresos_movimientos'], 0, ',', '.') . " Gs\n";
echo "Saldo esperado calculado: " . number_format($report['saldo_esperado_calculado'], 0, ',', '.') . " Gs\n";
if ($report['efectivo_real'] !== null) {
    echo "Efectivo real: " . number_format($report['efectivo_real'], 0, ',', '.') . " Gs\n";
    echo "Diferencia: " . number_format($report['diferencia'], 0, ',', '.') . " Gs ({$apertura->tipo_diferencia})\n";
}

echo "\n--- Breakdown por formas de pago (cobros) ---\n";
if (count($report['breakdown_formas_pago']) === 0) {
    echo "(No se encontraron formas de pago para cobros en esta apertura)\n";
} else {
    foreach ($report['breakdown_formas_pago'] as $tipo => $total) {
        echo " - {$tipo}: " . number_format($total, 0, ',', '.') . " Gs\n";
    }
}

echo "\n--- Movimientos (últimos) ---\n";
foreach ($report['movimientos'] as $m) {
    echo "[{$m['tipo_movimiento']}] {$m['concepto']} => " . number_format($m['monto'], 0, ',', '.') . " Gs (" . ($m['fecha_movimiento'] ?? '-') . ")\n";
}

// Guardar JSON en storage/arqueos
$path = __DIR__ . '/storage/arqueos';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}
$file = $path . "/arqueo_{$apertura->cod_apertura}.json";
file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\nArchivo guardado: {$file}\n";

exit(0);
