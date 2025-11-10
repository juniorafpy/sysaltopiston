<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleados;
use App\Models\AperturaCaja;
use App\Models\Caja;

echo "=== DIAGNÓSTICO COMPLETO ===\n\n";

// 1. Tu usuario
echo "1. TU USUARIO (ID: 2):\n";
$user = User::find(2);
if (!$user) {
    echo "   ❌ Usuario ID 2 NO EXISTE\n\n";
    exit(1);
}

echo "   Nombre: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   cod_empleado: " . ($user->cod_empleado ?? 'NULL') . "\n\n";

// 2. Empleado asociado
echo "2. EMPLEADO ASOCIADO:\n";
if (!$user->cod_empleado) {
    echo "   ❌ Usuario NO tiene empleado asociado\n";
    echo "   Solución: Edita el usuario y selecciona un empleado\n\n";
    exit(1);
}

$empleado = Empleados::with('persona')->find($user->cod_empleado);
if (!$empleado) {
    echo "   ❌ Empleado ID {$user->cod_empleado} NO EXISTE\n\n";
    exit(1);
}

echo "   ID Empleado: {$empleado->cod_empleado}\n";
echo "   Nombre: {$empleado->persona->nombre_completo}\n\n";

// 3. Aperturas de caja del empleado
echo "3. APERTURAS DE CAJA DEL EMPLEADO:\n";
$aperturas = AperturaCaja::where('cod_cajero', $empleado->cod_empleado)->get();

if ($aperturas->isEmpty()) {
    echo "   ❌ NO hay aperturas de caja para este empleado\n";
    echo "   Solución: Ve al módulo 'Apertura de Caja' y crea una apertura\n\n";
} else {
    foreach ($aperturas as $apertura) {
        $caja = Caja::find($apertura->cod_caja);
        echo "   - Apertura ID: {$apertura->cod_apertura}\n";
        echo "     Caja: " . ($caja ? $caja->descripcion : 'Desconocida') . "\n";
        echo "     Estado: {$apertura->estado}\n";
        echo "     Fecha: {$apertura->fecha_apertura}\n";
        echo "     Monto Inicial: {$apertura->monto_inicial}\n\n";
    }
}

// 4. Apertura ABIERTA
echo "4. APERTURA ABIERTA:\n";
$aperturaAbierta = AperturaCaja::where('cod_cajero', $empleado->cod_empleado)
    ->where('estado', 'Abierta')
    ->first();

if (!$aperturaAbierta) {
    echo "   ❌ NO hay apertura en estado 'Abierta'\n";
    echo "   Solución:\n";
    echo "   - Si tienes aperturas pero están cerradas, crea una nueva apertura\n";
    echo "   - Ve al módulo 'Apertura de Caja' y presiona 'Nueva Apertura'\n\n";
} else {
    $caja = Caja::find($aperturaAbierta->cod_caja);
    echo "   ✅ Apertura ABIERTA encontrada:\n";
    echo "      ID: {$aperturaAbierta->cod_apertura}\n";
    echo "      Caja ID: {$aperturaAbierta->cod_caja}\n";
    echo "      Caja: " . ($caja ? $caja->descripcion : 'Desconocida') . "\n";
    echo "      Monto Inicial: {$aperturaAbierta->monto_inicial}\n\n";

    // 5. Timbrado de la caja
    echo "5. TIMBRADO DE LA CAJA:\n";
    if ($caja) {
        $timbrado = $caja->timbradoActivo();
        if ($timbrado) {
            echo "   ✅ Timbrado encontrado:\n";
            echo "      ID: {$timbrado->cod_timbrado}\n";
            echo "      Número: {$timbrado->numero_timbrado}\n";
            echo "      Establecimiento: {$timbrado->establecimiento}\n";
            echo "      Punto Expedición: {$timbrado->punto_expedicion}\n";
            echo "      Números Disponibles: {$timbrado->numeros_disponibles}\n\n";
        } else {
            echo "   ❌ La caja NO tiene timbrado asignado\n";
            echo "   Solución: Ejecuta php artisan db:seed --class=CajaTimbradoSeeder\n\n";
        }
    }
}

// 6. Todas las cajas
echo "6. CAJAS EN EL SISTEMA:\n";
$cajas = Caja::all();
if ($cajas->isEmpty()) {
    echo "   ❌ NO hay cajas creadas\n\n";
} else {
    foreach ($cajas as $caja) {
        echo "   - Caja ID: {$caja->cod_caja} | {$caja->descripcion}\n";
    }
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
