@echo off
chcp 65001 >nul
cls
echo =============================================
echo   VERIFICACION DE TABLA
echo =============================================
echo.

php verify_notas_table.php

echo.
echo =============================================
echo   FIN DE VERIFICACION
echo =============================================
echo.
echo Presiona cualquier tecla para salir...
pause >nul
