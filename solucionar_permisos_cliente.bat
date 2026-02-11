@echo off
echo.
echo ===============================================
echo   SOLUCION COMPLETA - PERMISOS CLIENTE
echo ===============================================
echo.

echo [1/4] Generando permisos con Shield...
php artisan shield:generate --all

echo.
echo [2/4] Verificando permisos creados...
php artisan tinker --execute="$count = Spatie\Permission\Models\Permission::where('name', 'like', '%%cliente%%')->count(); echo '\nPermisos de Cliente: ' . $count . '\n';"

echo.
echo [3/4] Limpiando cache...
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo [4/4] Listando permisos...
php artisan tinker --execute="$permisos = Spatie\Permission\Models\Permission::where('name', 'like', '%%cliente%%')->get(); foreach($permisos as $p) { echo '  - ' . $p->name . '\n'; }"

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo AHORA DEBES HACER:
echo.
echo 1. PRESIONA Ctrl + Shift + R en tu navegador
echo    (Esto recarga sin cache)
echo.
echo 2. Ve a: Shield (icono escudo) - Roles
echo.
echo 3. Edita tu rol (ejemplo: super_admin)
echo.
echo 4. Busca la seccion "Cliente"
echo    (Deberia aparecer junto a otros recursos)
echo.
echo 5. Marca los checkboxes de los permisos:
echo    - view_any_cliente
echo    - view_cliente
echo    - create_cliente
echo    - update_cliente
echo    - delete_cliente
echo.
echo 6. Guarda los cambios
echo.
echo 7. El menu "Clientes" deberia aparecer en Definiciones
echo.
pause
