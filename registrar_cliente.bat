@echo off
echo.
echo ===============================================================
echo     REGISTRO MANUAL DE PERMISOS DE CLIENTE
echo ===============================================================
echo.

echo Ejecutando registro de permisos...
php registrar_permisos_cliente_manual.php

echo.
echo Limpiando cache de Laravel...
php artisan optimize:clear
php artisan config:clear
php artisan route:clear

echo.
echo ===============================================================
echo                        COMPLETADO
echo ===============================================================
echo.
echo AHORA:
echo   1. Presiona Ctrl + Shift + R en tu navegador
echo   2. Ve a: Shield (escudo) - Roles
echo   3. Edita tu rol (ejemplo: super_admin)
echo   4. Busca la seccion "Cliente" en los permisos
echo   5. Activa los permisos que necesites
echo   6. Guarda los cambios
echo.
pause
