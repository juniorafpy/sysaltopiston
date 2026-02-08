-- Script para eliminar tablas de notas de crédito/débito
-- Ejecutar en PostgreSQL

-- Eliminar tablas (en orden por dependencias)
DROP TABLE IF EXISTS nota_credito_debito_compra_detalles CASCADE;
DROP TABLE IF EXISTS nota_credito_debito_compras CASCADE;
DROP TABLE IF EXISTS motivos_nota_credito_debito CASCADE;

-- Eliminar registros de migraciones
DELETE FROM migrations WHERE migration LIKE '%nota_credito_debito%';
DELETE FROM migrations WHERE migration LIKE '%motivos_nota%';

-- Mensaje de confirmación
SELECT 'Tablas eliminadas correctamente. Ejecuta: php artisan migrate' as resultado;
