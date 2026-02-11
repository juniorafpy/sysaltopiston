@echo off
echo.
echo ===============================================
echo   GENERANDO PERMISOS CON SHIELD
echo ===============================================
echo.

echo Ejecutando: php artisan shield:generate --all
echo.
php artisan shield:generate --all

echo.
echo Limpiando cache...
php artisan optimize:clear

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo SIGUIENTES PASOS:
echo   1. Presiona Ctrl + Shift + R en tu navegador
echo   2. Ve a Shield - Roles
echo   3. Edita tu rol
echo   4. Ahora deberia aparecer "Cliente" en los permisos
echo   5. Activa los permisos que necesites
echo   6. Guarda cambios
echo.
pause
