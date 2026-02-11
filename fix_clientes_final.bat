@echo off
chcp 65001 >nul
cls
echo =============================================
echo   SOLUCION FINAL - CLIENTES
echo =============================================
echo.

echo Paso 1: Limpiando cache completa...
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo Paso 2: Regenerando rutas...
php artisan route:cache

echo.
echo =============================================
echo   COMPLETADO
echo =============================================
echo.
echo PersonasResource ha sido ocultado del menu
echo ClienteResource ahora usa la tabla personas
echo.
echo Acciones:
echo 1. Cierra sesion en Filament
echo 2. Vuelve a entrar
echo 3. Presiona Ctrl+Shift+R en el navegador
echo 4. Busca: Definiciones ^> Clientes
echo.
echo O accede directamente:
echo http://tu-dominio/admin/clientes
echo.
pause
