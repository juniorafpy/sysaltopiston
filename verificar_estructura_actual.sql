-- Verificar estructura actual de las tablas

-- Ver columnas de recepcion_vehiculos
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'recepcion_vehiculos'
  AND column_name LIKE '%cliente%'
ORDER BY ordinal_position;

-- Ver columnas de vehiculos
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'vehiculos'
  AND column_name LIKE '%cliente%'
ORDER BY ordinal_position;

-- Ver foreign keys actuales
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name,
    tc.constraint_name
FROM
    information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_name IN ('recepcion_vehiculos', 'vehiculos')
  AND kcu.column_name LIKE '%cliente%';
