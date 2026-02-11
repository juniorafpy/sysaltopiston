@echo off
echo.
echo ===============================================
echo   APLICANDO PROTECCION CONTRA ELIMINACION
echo ===============================================
echo.

echo Limpiando cache de Laravel...
php artisan optimize:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo PROTECCION APLICADA:
echo.
echo 1. No se pueden eliminar clientes con recepciones
echo    de vehiculos registradas
echo.
echo 2. Cuando intentes eliminar un cliente con
echo    recepciones, veras el mensaje:
echo    "Este cliente tiene recepciones de vehiculos.
echo     No es posible eliminarlo."
echo.
echo 3. ALTERNATIVA DISPONIBLE:
echo    En lugar de eliminar, usa el boton:
echo    - "Desactivar Cliente" (si esta activo)
echo    - "Activar Cliente" (si esta inactivo)
echo.
echo 4. Los clientes inactivos no apareceran en
echo    los selectores pero mantienen su historial
echo.
pause
