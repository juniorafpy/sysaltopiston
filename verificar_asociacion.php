<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Empleados;

echo "=== VERIFICACIÓN Y ASOCIACIÓN USER-EMPLEADO ===\n\n";

// 1. Verificar si la columna existe
echo "1. Verificando columna cod_empleado en users...\n";
if (Schema::hasColumn('users', 'cod_empleado')) {
    echo "   ✅ La columna cod_empleado EXISTE en users\n\n";
} else {
    echo "   ❌ La columna cod_empleado NO EXISTE en users\n";
    echo "   Ejecuta: php artisan migrate\n\n";
    exit(1);
}

// 2. Mostrar estructura de users
echo "2. Estructura de la tabla users:\n";
$columns = DB::select("SELECT column_name, data_type, is_nullable
                       FROM information_schema.columns
                       WHERE table_name = 'users'
                       ORDER BY ordinal_position");
foreach ($columns as $col) {
    echo "   - {$col->column_name} ({$col->data_type}) " . ($col->is_nullable === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
echo "\n";

// 3. Listar usuarios
echo "3. Usuarios en el sistema:\n";
$users = User::all();
if ($users->isEmpty()) {
    echo "   ❌ No hay usuarios\n\n";
} else {
    foreach ($users as $user) {
        $empleadoInfo = $user->cod_empleado ? "Empleado ID: {$user->cod_empleado}" : "Sin empleado";
        echo "   ID: {$user->id} | {$user->name} | {$user->email} | {$empleadoInfo}\n";
    }
    echo "\n";
}

// 4. Listar empleados
echo "4. Empleados en el sistema:\n";
$empleados = Empleados::with('persona')->get();
if ($empleados->isEmpty()) {
    echo "   ❌ No hay empleados\n\n";
} else {
    foreach ($empleados as $emp) {
        echo "   ID: {$emp->cod_empleado} | {$emp->persona->nombre_completo}\n";
    }
    echo "\n";
}

// 5. Asociar usuarios sin empleado
echo "5. Asociando usuarios sin empleado...\n";
if ($empleados->isNotEmpty() && $users->isNotEmpty()) {
    foreach ($users as $user) {
        if (!$user->cod_empleado) {
            $primerEmpleado = $empleados->first();
            $user->cod_empleado = $primerEmpleado->cod_empleado;
            $user->save();
            echo "   ✅ Usuario '{$user->name}' asociado con empleado '{$primerEmpleado->persona->nombre_completo}'\n";
        }
    }
} else {
    echo "   ⚠️ No se puede asociar (faltan usuarios o empleados)\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
