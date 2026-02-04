<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Insertando datos básicos...\n\n";

try {
    // Verificar/Insertar Almacenes
    $almacenesCount = DB::table('almacenes')->count();
    if ($almacenesCount == 0) {
        DB::table('almacenes')->insert([
            ['nombre' => 'Almacén Principal', 'descripcion' => 'Depósito principal de mercaderías', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Almacén Sucursal', 'descripcion' => 'Depósito de sucursal', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✓ 2 almacenes creados\n";
    } else {
        echo "✓ Ya existen {$almacenesCount} almacenes\n";
    }

    // Verificar Sucursales
    $sucursalesCount = DB::table('sucursal')->count();
    echo "✓ Existen {$sucursalesCount} sucursales\n";

    // Mostrar datos
    echo "\n--- ALMACENES ---\n";
    $almacenes = DB::table('almacenes')->select('id', 'nombre')->get();
    foreach ($almacenes as $alm) {
        echo "  ID: {$alm->id} - {$alm->nombre}\n";
    }

    echo "\n--- SUCURSALES ---\n";
    $sucursales = DB::table('sucursal')->select('cod_sucursal', 'descripcion')->get();
    foreach ($sucursales as $suc) {
        echo "  Código: {$suc->cod_sucursal} - {$suc->descripcion}\n";
    }

    echo "\n✅ Listo!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
