@echo off
echo.
echo ===============================================================
echo    GENERACION DE PERMISOS PARA CLIENTES - FILAMENT SHIELD
echo ===============================================================
echo.

echo [PASO 1/4] Generando permisos con FilamentShield...
php artisan shield:generate --all

echo.
echo [PASO 2/4] Ejecutando script personalizado...
php generar_permisos_cliente.php

echo.
echo [PASO 3/4] Limpiando cache...
php artisan optimize:clear
php artisan config:clear
php artisan route:clear

echo.
echo [PASO 4/4] Verificando permisos creados...
php artisan tinker --execute="echo '\n=== Permisos de Cliente ===\n'; $permisos = Spatie\Permission\Models\Permission::where('name', 'like', '%%%%cliente%%%%')->get(); echo 'Total: ' . $permisos->count() . '\n'; foreach($permisos as $p) { echo '  - ' . $p->name . '\n'; }"

echo.
echo ===============================================================
echo                        COMPLETADO
echo ===============================================================
echo.
echo SIGUIENTES PASOS:
echo    1. Presiona Ctrl + Shift + R en tu navegador
echo    2. Cierra sesion y vuelve a iniciar sesion en Filament
echo    3. Ve a: Definiciones - Clientes
echo.
echo Si aun no tienes acceso:
echo    1. Ve a: Shield - Roles
echo    2. Edita tu rol
echo    3. Activa los permisos de "Cliente"
echo    4. Guarda cambios
echo.
echo TIP: Si eres el usuario ID=1 tendras acceso automatico
echo.
pause
