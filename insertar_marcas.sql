-- Insertar marcas de vehículos
INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Toyota', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Toyota');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Chevrolet', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Chevrolet');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Ford', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Ford');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Honda', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Honda');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Nissan', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Nissan');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Hyundai', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Hyundai');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Volkswagen', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Volkswagen');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Kia', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Kia');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'Mercedes-Benz', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'Mercedes-Benz');

INSERT INTO marcas (descripcion, usuario_alta, fec_alta)
SELECT 'BMW', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE descripcion = 'BMW');

-- Verificar
SELECT * FROM marcas ORDER BY descripcion;
