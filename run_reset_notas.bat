@echo off
cls
echo =============================================
echo   RESET Y MIGRACION DE NOTAS DE CREDITO
echo =============================================
echo.

echo Paso 1: Eliminando tablas antiguas...
echo.
php reset_notas_migrations.php

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: El reset fallo. Revisa los errores arriba.
    pause
    exit /b 1
)

echo.
echo Paso 2: Ejecutando migraciones...
echo.
php artisan migrate

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: La migracion fallo. Revisa los errores arriba.
    pause
    exit /b 1
)

echo.
echo =============================================
echo   PROCESO COMPLETADO EXITOSAMENTE
echo =============================================
echo.
pause
