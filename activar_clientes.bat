@echo off
chcp 65001 >nul
cls
echo =============================================
echo   ACTIVAR RECURSO DE CLIENTES
echo =============================================
echo.

echo Paso 1: Generando permisos de Shield...
php artisan shield:generate --all

echo.
echo Paso 2: Limpiando toda la cache...
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo.
echo =============================================
echo   COMPLETADO
echo =============================================
echo.
echo Ahora:
echo 1. Presiona Ctrl+F5 en tu navegador
echo 2. Ve al menu: Definiciones ^> Clientes
echo.
echo Si aun no aparece, ve a:
echo - Configuracion ^> Roles
echo - Edita tu rol (ej: super_admin)
echo - Activa los permisos de "Cliente"
echo.
pause
