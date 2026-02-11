<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== REGISTRANDO PERMISOS DE CLIENTE MANUALMENTE ===\n\n";

// Los permisos que necesita el recurso Cliente
$permisosCliente = [
    'view_cliente',
    'view_any_cliente',
    'create_cliente',
    'update_cliente',
    'delete_cliente',
    'delete_any_cliente',
    'force_delete_cliente',
    'force_delete_any_cliente',
    'restore_cliente',
    'restore_any_cliente',
    'replicate_cliente',
    'reorder_cliente',
];

echo "Paso 1: Creando permisos en la tabla permissions...\n";
echo str_repeat("-", 60) . "\n";

foreach ($permisosCliente as $nombrePermiso) {
    // Verificar si ya existe
    $existe = DB::table('permissions')
        ->where('name', $nombrePermiso)
        ->exists();

    if (!$existe) {
        DB::table('permissions')->insert([
            'name' => $nombrePermiso,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  [+] Creado: {$nombrePermiso}\n";
    } else {
        echo "  [OK] Ya existe: {$nombrePermiso}\n";
    }
}

// Obtener todos los roles
echo "\n\nPaso 2: Asignando permisos a todos los roles...\n";
echo str_repeat("-", 60) . "\n";

$roles = DB::table('roles')->get();

foreach ($roles as $rol) {
    echo "\nRol: {$rol->name}\n";

    foreach ($permisosCliente as $nombrePermiso) {
        // Obtener el ID del permiso
        $permiso = DB::table('permissions')
            ->where('name', $nombrePermiso)
            ->first();

        if ($permiso) {
            // Verificar si ya está asignado
            $yaAsignado = DB::table('role_has_permissions')
                ->where('role_id', $rol->id)
                ->where('permission_id', $permiso->id)
                ->exists();

            if (!$yaAsignado) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permiso->id,
                    'role_id' => $rol->id,
                ]);
                echo "  [+] {$nombrePermiso}\n";
            }
        }
    }
}

// Verificación final
echo "\n\nPaso 3: Verificacion final...\n";
echo str_repeat("-", 60) . "\n";

$totalPermisos = DB::table('permissions')
    ->where('name', 'like', '%cliente%')
    ->count();

echo "Total de permisos de Cliente creados: {$totalPermisos}\n";

foreach ($roles as $rol) {
    $count = DB::table('role_has_permissions')
        ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
        ->where('role_has_permissions.role_id', $rol->id)
        ->where('permissions.name', 'like', '%cliente%')
        ->count();

    echo "Rol '{$rol->name}' tiene {$count} permisos de Cliente\n";
}

echo "\n\n=== COMPLETADO ===\n";
echo "Ahora ejecuta:\n";
echo "  1. php artisan optimize:clear\n";
echo "  2. Ctrl + Shift + R en el navegador\n";
echo "  3. Ve a Shield > Roles > [Tu Rol]\n";
echo "  4. Deberias ver los permisos de 'Cliente'\n\n";
