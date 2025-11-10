<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Empleados;

echo "=== ASOCIAR USUARIO CON EMPLEADO ===\n\n";

// Listar usuarios
echo "USUARIOS:\n";
$users = User::all();
if ($users->isEmpty()) {
    echo "No hay usuarios en el sistema.\n\n";
} else {
    foreach ($users as $user) {
        $empleadoInfo = $user->empleado ? "Empleado: {$user->empleado->persona->nombre_completo}" : "Sin empleado";
        echo "ID: {$user->id} | {$user->name} ({$user->email}) | {$empleadoInfo}\n";
    }
}

echo "\n";

// Listar empleados
echo "EMPLEADOS:\n";
$empleados = Empleados::with('persona')->get();
if ($empleados->isEmpty()) {
    echo "No hay empleados en el sistema.\n\n";
} else {
    foreach ($empleados as $empleado) {
        echo "ID: {$empleado->cod_empleado} | {$empleado->persona->nombre_completo}\n";
    }
}

echo "\n";

// Asociar primer usuario con primer empleado si no tiene empleado
$primerUser = User::first();
if ($primerUser && !$primerUser->cod_empleado) {
    $primerEmpleado = Empleados::first();
    if ($primerEmpleado) {
        $primerUser->cod_empleado = $primerEmpleado->cod_empleado;
        $primerUser->save();
        echo "✅ Usuario '{$primerUser->name}' asociado con empleado '{$primerEmpleado->persona->nombre_completo}'\n";
    } else {
        echo "❌ No hay empleados para asociar\n";
    }
} else {
    if ($primerUser) {
        echo "✅ El usuario ya tiene un empleado asociado\n";
    }
}
