@echo off
chcp 65001 >nul
echo.
echo ===============================================
echo   DIAGNÓSTICO DE PERMISOS - CLIENTES
echo ===============================================
echo.

echo [Paso 1] Generando permisos de FilamentShield...
php artisan shield:generate --all

echo.
echo [Paso 2] Verificando permisos en base de datos...
php artisan tinker --execute="echo '\n=== PERMISOS DE CLIENTE ===\n'; $permisos = \Spatie\Permission\Models\Permission::where('name', 'like', '%%cliente%%')->get(); if($permisos->count() > 0) { echo 'Se encontraron ' . $permisos->count() . ' permisos:\n'; foreach($permisos as $p) { echo '  - ' . $p->name . '\n'; } } else { echo 'NO se encontraron permisos de Cliente\n'; }"

echo.
echo [Paso 3] Verificando tu usuario y roles...
php artisan tinker --execute="$user = \App\Models\User::first(); echo '\nUsuario: ' . $user->name . '\n'; echo 'Email: ' . $user->email . '\n'; $roles = $user->roles; if($roles->count() > 0) { echo 'Roles:\n'; foreach($roles as $r) { echo '  - ' . $r->name . ' ('. $r->permissions->count() . ' permisos)\n'; } } else { echo 'Sin roles asignados\n'; }"

echo.
echo [Paso 4] Limpiando caché...
php artisan optimize:clear

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo Acciones siguientes:
echo   1. Presiona Ctrl + Shift + R en tu navegador
echo   2. Verifica que "Clientes" aparezca en el menú
echo   3. Si no aparece, asigna permisos desde:
echo      Admin panel ^> Roles ^> [Tu Rol] ^> Permisos
echo.
pause
