<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== VERIFICACIÓN DE PERMISOS - CLIENTES ===\n\n";

// 1. Verificar permisos para el recurso Cliente
echo "1. PERMISOS DEL RECURSO CLIENTE:\n";
echo str_repeat("-", 80) . "\n";

$permisosCliente = DB::table('permissions')
    ->where('name', 'like', '%cliente%')
    ->orWhere('name', 'like', '%Cliente%')
    ->get();

if ($permisosCliente->count() > 0) {
    echo "✓ Se encontraron " . $permisosCliente->count() . " permisos relacionados con Cliente:\n\n";
    foreach ($permisosCliente as $permiso) {
        echo "  ID: {$permiso->id}\n";
        echo "  Nombre: {$permiso->name}\n";
        echo "  Guard: {$permiso->guard_name}\n";
        echo "  Creado: {$permiso->created_at}\n";
        echo str_repeat("-", 80) . "\n";
    }
} else {
    echo "✗ NO se encontraron permisos para el recurso Cliente\n";
    echo "  → Necesitas ejecutar: php artisan shield:generate --all\n\n";
}

// 2. Verificar usuario actual y sus roles
echo "\n2. USUARIO ACTUAL Y SUS ROLES:\n";
echo str_repeat("-", 80) . "\n";

try {
    $user = auth()->user();
    if ($user) {
        echo "Usuario autenticado: {$user->name} (ID: {$user->id})\n";
        echo "Email: {$user->email}\n\n";

        // Obtener roles del usuario
        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.id', 'roles.name')
            ->get();

        if ($roles->count() > 0) {
            echo "Roles asignados:\n";
            foreach ($roles as $role) {
                echo "  - {$role->name} (ID: {$role->id})\n";
            }
        } else {
            echo "⚠ El usuario NO tiene roles asignados\n";
        }
    } else {
        echo "⚠ No hay usuario autenticado (ejecuta desde CLI)\n";
        echo "  Mostrando todos los usuarios del sistema:\n\n";

        $usuarios = DB::table('users')->get();
        foreach ($usuarios as $usuario) {
            echo "  - {$usuario->name} (ID: {$usuario->id}, Email: {$usuario->email})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Verificar todos los roles disponibles
echo "\n\n3. ROLES DISPONIBLES EN EL SISTEMA:\n";
echo str_repeat("-", 80) . "\n";

$rolesDisponibles = DB::table('roles')->get();

if ($rolesDisponibles->count() > 0) {
    foreach ($rolesDisponibles as $rol) {
        echo "\nRol: {$rol->name} (ID: {$rol->id})\n";

        // Contar permisos asignados a este rol
        $cantidadPermisos = DB::table('role_has_permissions')
            ->where('role_id', $rol->id)
            ->count();

        echo "  Permisos asignados: {$cantidadPermisos}\n";

        // Verificar si tiene permisos de Cliente
        $permisosClienteRol = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $rol->id)
            ->where(function($query) {
                $query->where('permissions.name', 'like', '%cliente%')
                      ->orWhere('permissions.name', 'like', '%Cliente%');
            })
            ->select('permissions.name')
            ->get();

        if ($permisosClienteRol->count() > 0) {
            echo "  ✓ Tiene permisos de Cliente:\n";
            foreach ($permisosClienteRol as $permiso) {
                echo "    - {$permiso->name}\n";
            }
        } else {
            echo "  ✗ NO tiene permisos de Cliente\n";
        }
    }
} else {
    echo "⚠ No hay roles en el sistema\n";
}

// 4. Verificar recursos de Filament registrados
echo "\n\n4. RECURSOS FILAMENT ESPERADOS:\n";
echo str_repeat("-", 80) . "\n";

$recursosEsperados = [
    'view_cliente',
    'view_any_cliente',
    'create_cliente',
    'update_cliente',
    'restore_cliente',
    'restore_any_cliente',
    'replicate_cliente',
    'reorder_cliente',
    'delete_cliente',
    'delete_any_cliente',
    'force_delete_cliente',
    'force_delete_any_cliente',
];

echo "Permisos estándar que deberían existir para el recurso Cliente:\n\n";
foreach ($recursosEsperados as $permiso) {
    $existe = DB::table('permissions')->where('name', $permiso)->exists();
    $icono = $existe ? '✓' : '✗';
    echo "  {$icono} {$permiso}\n";
}

// 5. Verificar si hay otros recursos con el mismo modelo
echo "\n\n5. PERMISOS DEL MODELO PERSONAS:\n";
echo str_repeat("-", 80) . "\n";

$permisosPersonas = DB::table('permissions')
    ->where('name', 'like', '%persona%')
    ->orWhere('name', 'like', '%Persona%')
    ->get();

if ($permisosPersonas->count() > 0) {
    echo "✓ Se encontraron " . $permisosPersonas->count() . " permisos relacionados con Personas:\n\n";
    foreach ($permisosPersonas as $permiso) {
        echo "  - {$permiso->name}\n";
    }
} else {
    echo "✗ NO se encontraron permisos para Personas\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "\nRECOMENDACIONES:\n";
echo "  1. Si NO existen permisos de Cliente: php artisan shield:generate --all\n";
echo "  2. Si el usuario no tiene permisos: Asignalos desde el panel de Roles\n";
echo "  3. Después de generar permisos: php artisan optimize:clear\n";
echo "  4. Recarga el navegador con Ctrl + Shift + R\n";
echo "\n";
