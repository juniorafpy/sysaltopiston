@echo off
echo.
echo ===============================================
echo   APLICAR VALIDACION UNIQUE A CLIENTES
echo ===============================================
echo.

echo [1/2] Agregando restriccion UNIQUE en base de datos...
php agregar_unique_clientes.php

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo agregar la restriccion.
    echo         Revisa los mensajes anteriores.
    pause
    exit /b 1
)

echo.
echo [2/2] Limpiando cache de Laravel...
php artisan optimize:clear >nul 2>&1
php artisan config:clear >nul 2>&1

echo.
echo ===============================================
echo   COMPLETADO CON EXITO
echo ===============================================
echo.
echo VALIDACION APLICADA:
echo   - Campo cod_persona ahora es UNIQUE en la tabla
echo   - El formulario valida antes de guardar
echo   - No se pueden registrar 2 clientes con la misma persona
echo.
echo MENSAJE AL USUARIO:
echo   Si intenta registrar un cliente con una persona
echo   que ya esta registrada, vera el mensaje:
echo   "Esta persona ya esta registrada como cliente"
echo.
pause
