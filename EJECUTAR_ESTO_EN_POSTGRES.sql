-- =============================================
-- SOLUCIÓN DEFINITIVA: Agregar columna cod_motivo
-- Ejecutar en pgAdmin, DBeaver, o tu cliente PostgreSQL
-- =============================================

-- PASO 1: Verificar si la columna existe
SELECT column_name
FROM information_schema.columns
WHERE table_name = 'nota_credito_debito_compras'
  AND column_name = 'cod_motivo';
-- Si no devuelve nada, la columna NO existe

-- PASO 2: Agregar la columna (ejecuta esto solo si el paso 1 no devolvió nada)
ALTER TABLE nota_credito_debito_compras
ADD COLUMN cod_motivo BIGINT NULL;

-- PASO 3: Agregar el foreign key
ALTER TABLE nota_credito_debito_compras
ADD CONSTRAINT nota_credito_debito_compras_cod_motivo_foreign
FOREIGN KEY (cod_motivo)
REFERENCES motivos_nota_credito_debito(cod_motivo)
ON DELETE RESTRICT;

-- PASO 4: Crear índice
CREATE INDEX nota_credito_debito_compras_cod_motivo_index
ON nota_credito_debito_compras(cod_motivo);

-- VERIFICACIÓN FINAL: Mostrar todas las columnas de la tabla
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'nota_credito_debito_compras'
ORDER BY ordinal_position;
-- Debes ver cod_motivo en la lista
