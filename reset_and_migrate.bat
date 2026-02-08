@echo off
echo Reseteando tablas de notas de credito/debito...
echo.

php reset_notas_migrations.php

echo.
echo Ejecutando migraciones...
php artisan migrate

echo.
echo ¡Proceso completado!
pause
