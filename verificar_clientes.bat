@echo off
chcp 65001 >nul
cls
echo =============================================
echo   VERIFICAR RECURSO DE CLIENTES
echo =============================================
echo.

echo Verificando archivo ClienteResource.php...
if exist "app\Filament\Resources\ClienteResource.php" (
    echo [OK] ClienteResource.php existe
) else (
    echo [ERROR] ClienteResource.php NO existe
)
echo.

echo Verificando páginas...
if exist "app\Filament\Resources\ClienteResource\Pages\ListClientes.php" (
    echo [OK] ListClientes.php existe
) else (
    echo [ERROR] ListClientes.php NO existe
)

if exist "app\Filament\Resources\ClienteResource\Pages\CreateCliente.php" (
    echo [OK] CreateCliente.php existe
) else (
    echo [ERROR] CreateCliente.php NO existe
)

if exist "app\Filament\Resources\ClienteResource\Pages\EditCliente.php" (
    echo [OK] EditCliente.php existe
) else (
    echo [ERROR] EditCliente.php NO existe
)

if exist "app\Filament\Resources\ClienteResource\Pages\ViewCliente.php" (
    echo [OK] ViewCliente.php existe
) else (
    echo [ERROR] ViewCliente.php NO existe
)
echo.

echo Limpiando caché...
php artisan optimize:clear

echo.
echo =============================================
echo   VERIFICACION COMPLETA
echo =============================================
echo.
echo Si todos los archivos existen:
echo 1. Presiona Ctrl+F5 en tu navegador
echo 2. Busca en el menu: Ventas ^> Clientes
echo.
pause
