<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Verificando columnas de la tabla 'cobros'...\n";
echo str_repeat("-", 60) . "\n";

try {
    $columns = DB::select("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'cobros'
        ORDER BY ordinal_position
    ");

    if (empty($columns)) {
        echo "⚠️ No se encontraron columnas para la tabla 'cobros'\n";
        echo "La tabla puede no existir o estar vacía.\n";
    } else {
        echo "Columnas encontradas:\n\n";
        foreach ($columns as $column) {
            echo "- {$column->column_name} ({$column->data_type}) ";
            echo $column->is_nullable === 'YES' ? "NULL" : "NOT NULL";
            if ($column->column_default) {
                echo " DEFAULT: {$column->column_default}";
            }
            echo "\n";
        }
        echo "\nTotal: " . count($columns) . " columnas\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
