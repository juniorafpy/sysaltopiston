<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Verificando tablas de cobros...\n";
echo "================================\n\n";

$tables = ['cobros', 'cobros_detalle', 'cobros_formas_pago'];

foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo "Tabla '$table': " . ($exists ? "✓ EXISTE" : "✗ NO EXISTE") . "\n";
}

echo "\n================================\n";
echo "Verificación completada.\n";
