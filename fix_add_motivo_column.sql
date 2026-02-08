-- =============================================
-- FIX: Agregar columna cod_motivo
-- =============================================

-- 1. Crear tabla de motivos si no existe
CREATE TABLE IF NOT EXISTS motivos_nota_credito_debito (
    cod_motivo BIGSERIAL PRIMARY KEY,
    tipo_nota VARCHAR(2) NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    afecta_stock BOOLEAN DEFAULT FALSE,
    afecta_saldo BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Insertar motivos si la tabla está vacía
INSERT INTO motivos_nota_credito_debito (tipo_nota, descripcion, afecta_stock, afecta_saldo, activo, created_at, updated_at)
SELECT * FROM (VALUES
    ('NC', 'Devolución de mercadería', TRUE, TRUE, TRUE, NOW(), NOW()),
    ('NC', 'Descuento comercial', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('NC', 'Error en precio facturado', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('NC', 'Mercadería dañada o vencida', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('NC', 'Error en cantidad facturada', TRUE, TRUE, TRUE, NOW(), NOW()),
    ('NC', 'Bonificación posterior', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('ND', 'Intereses por mora', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('ND', 'Gastos de envío adicionales', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('ND', 'Error en precio (menor al real)', FALSE, TRUE, TRUE, NOW(), NOW()),
    ('ND', 'Ajuste de precio por diferencia cambiaria', FALSE, TRUE, TRUE, NOW(), NOW())
) AS v(tipo_nota, descripcion, afecta_stock, afecta_saldo, activo, created_at, updated_at)
WHERE NOT EXISTS (SELECT 1 FROM motivos_nota_credito_debito LIMIT 1);

-- 3. Agregar columna cod_motivo si no existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'nota_credito_debito_compras'
        AND column_name = 'cod_motivo'
    ) THEN
        ALTER TABLE nota_credito_debito_compras
        ADD COLUMN cod_motivo BIGINT NULL;

        ALTER TABLE nota_credito_debito_compras
        ADD CONSTRAINT nota_credito_debito_compras_cod_motivo_foreign
        FOREIGN KEY (cod_motivo)
        REFERENCES motivos_nota_credito_debito(cod_motivo)
        ON DELETE RESTRICT;

        CREATE INDEX nota_credito_debito_compras_cod_motivo_index
        ON nota_credito_debito_compras(cod_motivo);

        RAISE NOTICE 'Columna cod_motivo agregada exitosamente';
    ELSE
        RAISE NOTICE 'La columna cod_motivo ya existe';
    END IF;
END $$;

-- Verificación final
SELECT
    'OK - Tabla motivos tiene ' || COUNT(*) || ' registros' as resultado
FROM motivos_nota_credito_debito;

SELECT
    'OK - Columna cod_motivo existe en nota_credito_debito_compras' as resultado
WHERE EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'nota_credito_debito_compras'
    AND column_name = 'cod_motivo'
);
