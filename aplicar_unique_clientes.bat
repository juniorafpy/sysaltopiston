@echo off
echo.
echo ===============================================
echo   AGREGAR UNIQUE A CLIENTES.COD_PERSONA
echo ===============================================
echo.

php agregar_unique_clientes.php

echo.
echo Limpiando cache...
php artisan optimize:clear >nul 2>&1

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo Ahora NO se podran registrar dos clientes
echo con el mismo codigo de persona.
echo.
echo Si intentas guardar un cliente con un
echo cod_persona que ya existe, aparecera un error:
echo   "Esta persona ya esta registrada como cliente."
echo.
pause
