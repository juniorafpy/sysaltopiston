<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== VERIFICACIÓN DE TABLA CLIENTES ===\n\n";

// Verificar si existe tabla clientes
$tablaClientes = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'clientes'");

if (count($tablaClientes) > 0) {
    echo "✓ La tabla 'clientes' EXISTE en la base de datos\n\n";

    echo "Estructura de la tabla 'clientes':\n";
    echo str_repeat("-", 80) . "\n";

    $columns = DB::select("
        SELECT
            column_name,
            data_type,
            character_maximum_length,
            is_nullable,
            column_default
        FROM information_schema.columns
        WHERE table_name = 'clientes'
        ORDER BY ordinal_position
    ");

    foreach ($columns as $column) {
        $type = $column->data_type;
        if ($column->character_maximum_length) {
            $type .= "({$column->character_maximum_length})";
        }
        $nullable = $column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->column_default ? " DEFAULT {$column->column_default}" : '';

        echo sprintf("%-30s %-20s %-10s %s\n",
            $column->column_name,
            $type,
            $nullable,
            $default
        );
    }

    // Verificar foreign keys
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Foreign Keys:\n";

    $fks = DB::select("
        SELECT
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name='clientes'
    ");

    if (count($fks) > 0) {
        foreach ($fks as $fk) {
            echo "  - {$fk->column_name} -> {$fk->foreign_table_name}({$fk->foreign_column_name})\n";
        }
    } else {
        echo "  No hay foreign keys\n";
    }

} else {
    echo "✗ La tabla 'clientes' NO EXISTE en la base de datos\n\n";
    echo "El sistema probablemente usa la tabla 'personas' para gestionar clientes.\n";
    echo "Verificando tabla 'personas'...\n\n";

    $tablaPersonas = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'personas'");

    if (count($tablaPersonas) > 0) {
        echo "✓ La tabla 'personas' SÍ EXISTE\n\n";

        echo "Estructura de la tabla 'personas':\n";
        echo str_repeat("-", 80) . "\n";

        $columns = DB::select("
            SELECT
                column_name,
                data_type,
                character_maximum_length,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_name = 'personas'
            ORDER BY ordinal_position
        ");

        foreach ($columns as $column) {
            $type = $column->data_type;
            if ($column->character_maximum_length) {
                $type .= "({$column->character_maximum_length})";
            }
            $nullable = $column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column->column_default ? " DEFAULT {$column->column_default}" : '';

            echo sprintf("%-30s %-20s %-10s %s\n",
                $column->column_name,
                $type,
                $nullable,
                $default
            );
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
