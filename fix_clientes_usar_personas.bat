@echo off
chcp 65001 >nul
cls
echo =============================================
echo   SOLUCION: Usar PersonasResource como Clientes
echo =============================================
echo.

echo ClienteResource eliminado
echo PersonasResource ahora se llama "Clientes" en el menu
echo.

echo Limpiando cache...
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo.
echo =============================================
echo   LISTO
echo =============================================
echo.
echo Ahora:
echo 1. Presiona Ctrl+Shift+R en el navegador
echo 2. Busca: Definiciones ^> Clientes
echo.
echo La URL sera:
echo http://localhost/admin/personas
echo.
pause
