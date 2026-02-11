-- Script para modificar foreign keys de personas a clientes
-- Ejecutar en PostgreSQL

BEGIN;

-- ==========================================
-- MODIFICAR TABLA recepcion_vehiculos
-- ==========================================

-- 1. Eliminar foreign key existente
ALTER TABLE recepcion_vehiculos
DROP CONSTRAINT IF EXISTS recepcion_vehiculos_cliente_id_foreign;

-- 2. Renombrar columna cliente_id a cod_cliente
ALTER TABLE recepcion_vehiculos
RENAME COLUMN cliente_id TO cod_cliente;

-- 3. Agregar nueva foreign key apuntando a clientes
ALTER TABLE recepcion_vehiculos
ADD CONSTRAINT recepcion_vehiculos_cod_cliente_foreign
FOREIGN KEY (cod_cliente)
REFERENCES clientes(cod_cliente)
ON DELETE RESTRICT;

-- ==========================================
-- MODIFICAR TABLA vehiculos
-- ==========================================

-- 1. Eliminar foreign key existente
ALTER TABLE vehiculos
DROP CONSTRAINT IF EXISTS vehiculos_cliente_id_foreign;

-- 2. Renombrar columna cliente_id a cod_cliente
ALTER TABLE vehiculos
RENAME COLUMN cliente_id TO cod_cliente;

-- 3. Agregar nueva foreign key apuntando a clientes
ALTER TABLE vehiculos
ADD CONSTRAINT vehiculos_cod_cliente_foreign
FOREIGN KEY (cod_cliente)
REFERENCES clientes(cod_cliente)
ON DELETE RESTRICT;

COMMIT;

-- Verificar los cambios
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM
    information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_name IN ('recepcion_vehiculos', 'vehiculos')
  AND kcu.column_name IN ('cod_cliente', 'cliente_id');
