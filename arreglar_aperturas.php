<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AperturaCaja;
use App\Models\User;
use App\Models\Empleados;
use Illuminate\Support\Facades\DB;

echo "=== ARREGLAR APERTURAS DE CAJA ===\n\n";

// 1. Verificar aperturas
echo "1. APERTURAS EXISTENTES:\n";
$aperturas = AperturaCaja::all();
if ($aperturas->isEmpty()) {
    echo "   ❌ No hay aperturas de caja\n\n";
    exit(0);
}

foreach ($aperturas as $apertura) {
    echo "   Apertura ID: {$apertura->cod_apertura}\n";
    echo "   cod_cajero actual: {$apertura->cod_cajero}\n";
    echo "   Caja: {$apertura->cod_caja}\n";
    echo "   Estado: {$apertura->estado}\n";

    // Verificar si cod_cajero es un user_id o empleado_id
    $esUserId = User::find($apertura->cod_cajero);
    $esEmpleadoId = Empleados::find($apertura->cod_cajero);

    if ($esUserId && !$esEmpleadoId) {
        echo "   ⚠️  cod_cajero es un USER_ID (necesita corrección)\n";

        // Buscar empleado del usuario
        if ($esUserId->empleado) {
            $nuevoId = $esUserId->empleado->cod_empleado;
            echo "   ✓  Empleado del usuario: ID {$nuevoId} - {$esUserId->empleado->persona->nombre_completo}\n";

            // Actualizar
            DB::table('aperturas_caja')
                ->where('cod_apertura', $apertura->cod_apertura)
                ->update(['cod_cajero' => $nuevoId]);

            echo "   ✅ ACTUALIZADO a empleado ID: {$nuevoId}\n";
        } else {
            echo "   ❌ Usuario no tiene empleado asociado\n";
        }
    } elseif ($esEmpleadoId) {
        echo "   ✅ cod_cajero ya es un EMPLEADO_ID (correcto): {$esEmpleadoId->persona->nombre_completo}\n";
    }

    echo "\n";
}

echo "=== PROCESO COMPLETADO ===\n";
