@echo off
chcp 65001 >nul
cls
echo =============================================
echo   FORZAR APARICION DE CLIENTES
echo =============================================
echo.

echo Limpiando TODA la cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo.
echo Regenerando permisos de Shield...
php artisan shield:generate --all --option=policies

echo.
echo =============================================
echo   IMPORTANTE
echo =============================================
echo.
echo 1. Presiona Ctrl+Shift+R en tu navegador (recarga total)
echo 2. Cierra sesion y vuelve a entrar
echo 3. Busca: Definiciones ^> Clientes
echo.
echo La URL deberia ser:
echo http://localhost/admin/clientes
echo.
echo Si NO aparece aun, ve manualmente a:
echo http://localhost/admin/clientes
echo.
pause
