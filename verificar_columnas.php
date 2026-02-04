<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Columnas en cm_presupuesto_cabecera:\n";
echo "=====================================\n";

$columns = Schema::getColumnListing('cm_presupuesto_cabecera');
foreach ($columns as $column) {
    echo "- $column\n";
}

echo "\n\nTipo de columnas de totales:\n";
echo "============================\n";

try {
    $columnTypes = DB::select("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'cm_presupuesto_cabecera'
        AND COLUMN_NAME IN ('monto_gravado', 'monto_tot_impuesto', 'monto_general')
        ORDER BY COLUMN_NAME
    ");

    foreach ($columnTypes as $col) {
        echo "Columna: {$col->COLUMN_NAME}\n";
        echo "  Tipo: {$col->DATA_TYPE}\n";
        echo "  Nullable: {$col->IS_NULLABLE}\n";
        echo "  Default: {$col->COLUMN_DEFAULT}\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
