<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\AperturaCaja;
use App\Models\Caja;

echo "=== CREAR APERTURA DE CAJA ===\n\n";

// 1. Obtener usuario ID 2
$user = User::find(2);
if (!$user) {
    echo "❌ Usuario ID 2 no existe\n";
    exit(1);
}

echo "Usuario: {$user->name}\n";

// 2. Verificar empleado
if (!$user->cod_empleado) {
    echo "❌ Usuario no tiene empleado asociado\n";
    echo "Asignando primer empleado disponible...\n";
    $primerEmpleado = \App\Models\Empleados::first();
    if ($primerEmpleado) {
        $user->cod_empleado = $primerEmpleado->cod_empleado;
        $user->save();
        echo "✅ Empleado asignado: {$primerEmpleado->persona->nombre_completo}\n";
    } else {
        echo "❌ No hay empleados en el sistema\n";
        exit(1);
    }
}

$empleado = $user->empleado;
echo "Empleado: {$empleado->persona->nombre_completo} (ID: {$empleado->cod_empleado})\n\n";

// 3. Verificar si ya tiene apertura abierta
$aperturaExistente = AperturaCaja::where('cod_cajero', $empleado->cod_empleado)
    ->where('estado', 'Abierta')
    ->first();

if ($aperturaExistente) {
    echo "✅ Ya tiene una apertura abierta:\n";
    echo "   ID: {$aperturaExistente->cod_apertura}\n";
    echo "   Caja: {$aperturaExistente->cod_caja}\n";
    echo "   Fecha: {$aperturaExistente->fecha_apertura}\n";
    exit(0);
}

// 4. Obtener primera caja disponible
$caja = Caja::first();
if (!$caja) {
    echo "❌ No hay cajas en el sistema\n";
    echo "Creando caja...\n";
    $caja = Caja::create([
        'descripcion' => 'Caja Principal',
        'ind_activo' => 'S',
    ]);
    echo "✅ Caja creada: {$caja->descripcion}\n";
}

echo "Caja: {$caja->descripcion} (ID: {$caja->cod_caja})\n\n";

// 5. Crear apertura
echo "Creando apertura de caja...\n";
$apertura = AperturaCaja::create([
    'cod_caja' => $caja->cod_caja,
    'cod_cajero' => $empleado->cod_empleado,
    'fecha_apertura' => now(),
    'hora_apertura' => now()->format('H:i:s'),
    'monto_inicial' => 0,
    'estado' => 'Abierta',
    'usuario_alta' => $user->email,
    'fec_alta' => now(),
]);

echo "✅ Apertura creada exitosamente:\n";
echo "   ID: {$apertura->cod_apertura}\n";
echo "   Estado: {$apertura->estado}\n";
echo "   Monto inicial: {$apertura->monto_inicial}\n\n";

echo "=== LISTO PARA FACTURAR ===\n";
