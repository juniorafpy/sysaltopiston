<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Verificando tablas de cobros...\n";
echo str_repeat("=", 60) . "\n\n";

$tablas = ['cobros', 'cobros_detalle', 'cobros_formas_pago'];

foreach ($tablas as $tabla) {
    $existe = Schema::hasTable($tabla);
    if ($existe) {
        $count = DB::table($tabla)->count();
        echo "✓ Tabla '{$tabla}' existe (registros: {$count})\n";
    } else {
        echo "✗ Tabla '{$tabla}' NO EXISTE\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
