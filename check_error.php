<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Verificando errores en cobros...\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // 1. Verificar tablas
    echo "1. Verificando tablas...\n";
    $tables = ['cobros', 'cobros_detalle', 'cobros_formas_pago'];
    foreach ($tables as $table) {
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        echo "   - {$table}: " . ($exists ? "✓ Existe" : "✗ NO existe") . "\n";
    }
    echo "\n";

    // 2. Verificar modelo Cobro
    echo "2. Verificando modelo Cobro...\n";
    try {
        $cobro = \App\Models\Cobro::first();
        echo "   ✓ Modelo Cobro cargado correctamente\n";
    } catch (\Exception $e) {
        echo "   ✗ Error en modelo Cobro: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 3. Verificar apertura actual
    echo "3. Verificando apertura de caja...\n";
    try {
        $user = \App\Models\User::first();
        if ($user && $user->empleado) {
            $apertura = \App\Models\AperturaCaja::where('cod_cajero', $user->empleado->cod_empleado)
                ->where('estado', 'Abierta')
                ->first();
            
            if ($apertura) {
                echo "   ✓ Apertura encontrada: {$apertura->cod_apertura}\n";
            } else {
                echo "   ⚠ No hay apertura abierta para el usuario\n";
            }
        } else {
            echo "   ⚠ Usuario no tiene empleado asociado\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Error verificando apertura: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. Verificar facturas con saldo
    echo "4. Verificando facturas con saldo...\n";
    try {
        $facturas = \App\Models\Factura::where('condicion_venta', 'Crédito')
            ->where('estado', 'Emitida')
            ->limit(5)
            ->get();
        
        echo "   Facturas encontradas: " . $facturas->count() . "\n";
        foreach ($facturas as $factura) {
            try {
                $saldo = $factura->getSaldoConNotas();
                echo "   - {$factura->numero_factura}: Saldo = " . number_format($saldo, 0, ',', '.') . " Gs\n";
            } catch (\Exception $e) {
                echo "   - {$factura->numero_factura}: Error al calcular saldo - " . $e->getMessage() . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ✗ Error verificando facturas: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 5. Verificar último error de logs
    echo "5. Último error en logs:\n";
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $lastLines = array_slice($lines, -50);
        $errors = array_filter($lastLines, function($line) {
            return strpos($line, 'ERROR') !== false || strpos($line, 'SQLSTATE') !== false;
        });
        
        if (!empty($errors)) {
            echo "   Últimos errores:\n";
            foreach (array_slice($errors, -5) as $error) {
                echo "   " . trim($error) . "\n";
            }
        } else {
            echo "   ✓ No se encontraron errores recientes\n";
        }
    } else {
        echo "   ⚠ Archivo de log no encontrado\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR GENERAL:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
