@echo off
echo.
echo ===============================================
echo   CONFIGURANDO TABLA CLIENTES
echo ===============================================
echo.

echo [1/3] Ejecutando migracion de tabla clientes...
php artisan migrate --path=database/migrations/2025_11_15_100000_create_clientes_table.php

echo.
echo [2/3] Generando permisos con Shield...
php artisan shield:generate --all

echo.
echo [3/3] Limpiando cache...
php artisan optimize:clear
php artisan route:clear
php artisan config:clear

echo.
echo ===============================================
echo   COMPLETADO
echo ===============================================
echo.
echo La tabla clientes ha sido creada con:
echo   - cod_cliente (PK)
echo   - cod_persona (FK a personas)
echo   - estado (A=Activo, I=Inactivo)
echo   - fec_alta
echo   - usuario_alta
echo.
echo Ahora:
echo   1. Presiona Ctrl + Shift + R en tu navegador
echo   2. Ve a Shield - Roles y activa permisos de Cliente
echo   3. El menu Clientes aparecera en Definiciones
echo.
pause
