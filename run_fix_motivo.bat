@echo off
chcp 65001 >nul
cls
echo =============================================
echo   FIX: AGREGAR COLUMNA COD_MOTIVO
echo =============================================
echo.

php fix_add_motivo_column.php

echo.
echo =============================================
if %ERRORLEVEL% EQU 0 (
    echo   EXITO - Columna agregada
) else (
    echo   ERROR - Revisa el mensaje arriba
)
echo =============================================
echo.
pause
