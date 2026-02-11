<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== DIAGNOSTICO: CLIENTES EN SHIELD ===\n\n";

// 1. Verificar si existe el archivo ClienteResource
$rutaRecurso = app_path('Filament/Resources/ClienteResource.php');
echo "1. VERIFICANDO ARCHIVO:\n";
echo str_repeat("-", 60) . "\n";
if (file_exists($rutaRecurso)) {
    echo "[OK] ClienteResource.php existe\n";
    echo "     Ubicacion: {$rutaRecurso}\n";
} else {
    echo "[ERROR] ClienteResource.php NO existe\n";
    exit(1);
}

// 2. Verificar permisos en la base de datos
echo "\n2. PERMISOS EN BASE DE DATOS:\n";
echo str_repeat("-", 60) . "\n";

$permisos = DB::table('permissions')
    ->where('name', 'like', '%cliente%')
    ->get();

if ($permisos->count() > 0) {
    echo "[OK] Se encontraron {$permisos->count()} permisos de Cliente:\n";
    foreach ($permisos as $p) {
        echo "  - {$p->name} (ID: {$p->id})\n";
    }
} else {
    echo "[X] NO hay permisos de Cliente en la base de datos\n";
}

// 3. Verificar roles
echo "\n3. ROLES Y SUS PERMISOS DE CLIENTE:\n";
echo str_repeat("-", 60) . "\n";

$roles = DB::table('roles')->get();

foreach ($roles as $rol) {
    $countPermisos = DB::table('role_has_permissions')
        ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
        ->where('role_has_permissions.role_id', $rol->id)
        ->where('permissions.name', 'like', '%cliente%')
        ->count();

    echo "Rol: {$rol->name}\n";
    echo "  Permisos de Cliente: {$countPermisos}\n";

    if ($countPermisos > 0) {
        $permisosRol = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $rol->id)
            ->where('permissions.name', 'like', '%cliente%')
            ->select('permissions.name')
            ->get();

        foreach ($permisosRol as $p) {
            echo "    - {$p->name}\n";
        }
    }
    echo "\n";
}

// 4. Contar todos los recursos en Filament
echo "4. RECURSOS DE FILAMENT:\n";
echo str_repeat("-", 60) . "\n";

$recursosPath = app_path('Filament/Resources');
$archivos = glob($recursosPath . '/*Resource.php');

echo "Total de *Resource.php encontrados: " . count($archivos) . "\n";
foreach ($archivos as $archivo) {
    $nombre = basename($archivo);
    echo "  - {$nombre}\n";
}

// 5. Verificación de permisos específicos necesarios
echo "\n5. PERMISOS NECESARIOS PARA CLIENTE:\n";
echo str_repeat("-", 60) . "\n";

$permisosNecesarios = [
    'view_any_cliente',
    'view_cliente',
    'create_cliente',
    'update_cliente',
    'delete_cliente',
];

foreach ($permisosNecesarios as $permiso) {
    $existe = DB::table('permissions')->where('name', $permiso)->exists();
    $icono = $existe ? '[OK]' : '[X]';
    echo "{$icono} {$permiso}\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "CONCLUSION:\n";

$totalPermisos = DB::table('permissions')->where('name', 'like', '%cliente%')->count();

if ($totalPermisos == 0) {
    echo "\n[ACCION REQUERIDA] No hay permisos de Cliente.\n";
    echo "Ejecuta: registrar_cliente.bat\n";
} elseif ($totalPermisos < 12) {
    echo "\n[ADVERTENCIA] Existen {$totalPermisos} permisos, pero deberian ser 12.\n";
    echo "Ejecuta: registrar_cliente.bat para completarlos\n";
} else {
    echo "\n[OK] Todo correcto. {$totalPermisos} permisos de Cliente registrados.\n";
    echo "\nSi aun no ves los permisos en Shield:\n";
    echo "  1. Ejecuta: php artisan optimize:clear\n";
    echo "  2. Ctrl + Shift + R en navegador\n";
    echo "  3. Ve a Shield > Roles y verifica que esten disponibles\n";
}

echo "\n";
