-- Insertar colores comunes de vehículos

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Blanco', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Blanco');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Negro', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Negro');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Plata', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Plata');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Gris', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Gris');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Rojo', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Rojo');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Azul', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Azul');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Verde', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Verde');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Amarillo', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Amarillo');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Naranja', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Naranja');

INSERT INTO colores (descripcion, usuario_alta, fec_alta)
SELECT 'Marrón', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM colores WHERE descripcion = 'Marrón');

-- Verificar
SELECT * FROM colores ORDER BY descripcion;
