-- Insertar modelos de vehículos por marca

-- Primero verificamos los cod_marca
-- Toyota
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Corolla', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Toyota'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Corolla');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Hilux', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Toyota'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Hilux');

-- Chevrolet
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Cruze', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Chevrolet'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Cruze');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'S10', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Chevrolet'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'S10');

-- Ford
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Ranger', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Ford'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Ranger');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Focus', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Ford'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Focus');

-- Honda
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Civic', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Honda'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Civic');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'CR-V', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Honda'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'CR-V');

-- Nissan
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Frontier', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Nissan'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Frontier');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Sentra', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Nissan'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Sentra');

-- Hyundai
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Tucson', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Hyundai'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Tucson');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Elantra', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Hyundai'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Elantra');

-- Volkswagen
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Amarok', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Volkswagen'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Amarok');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Golf', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Volkswagen'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Golf');

-- Kia
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Sportage', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Kia'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Sportage');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Rio', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Kia'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Rio');

-- Mercedes-Benz
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Clase C', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Mercedes-Benz'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Clase C');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'GLA', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'Mercedes-Benz'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'GLA');

-- BMW
INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'Serie 3', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'BMW'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'Serie 3');

INSERT INTO st_modelos (descripcion, cod_marca, usuario_alta, fec_alta)
SELECT 'X5', cod_marca, 'admin', NOW()
FROM marcas WHERE descripcion = 'BMW'
AND NOT EXISTS (SELECT 1 FROM st_modelos WHERE descripcion = 'X5');

-- Verificar los modelos insertados
SELECT m.cod_modelo, m.descripcion, ma.descripcion as marca
FROM st_modelos m
JOIN marcas ma ON m.cod_marca = ma.cod_marca
ORDER BY ma.descripcion, m.descripcion;
