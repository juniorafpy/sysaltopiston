-- Script para crear manualmente las tablas de reclamos
-- Ejecutar en PostgreSQL

-- 1. Crear tabla tipo_reclamos
CREATE TABLE tipo_reclamos (
    cod_tipo_reclamo BIGSERIAL PRIMARY KEY,
    descripcion VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- 2. Insertar tipos predefinidos
INSERT INTO tipo_reclamos (descripcion, activo, created_at, updated_at) VALUES
('Falla de Repuesto', true, NOW(), NOW()),
('Demora en el Servicio', true, NOW(), NOW()),
('Calidad de Servicio', true, NOW(), NOW()),
('Atención al Cliente', true, NOW(), NOW()),
('Precio/Facturación', true, NOW(), NOW()),
('Otros', true, NOW(), NOW());

-- 3. Crear tabla reclamos
CREATE TABLE reclamos (
    cod_reclamo BIGSERIAL PRIMARY KEY,
    cod_cliente BIGINT NOT NULL,
    orden_servicio_id BIGINT NOT NULL,
    cod_tipo_reclamo BIGINT NOT NULL,
    fecha_reclamo DATE NOT NULL,
    prioridad VARCHAR(10) DEFAULT 'Media' CHECK (prioridad IN ('Alta', 'Media', 'Baja')),
    descripcion TEXT NOT NULL,
    estado VARCHAR(20) DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente', 'En Proceso', 'Resuelto', 'Cerrado')),
    resolucion TEXT,
    fecha_resolucion DATE,
    usuario_resolucion BIGINT,
    cod_sucursal BIGINT,
    usuario_alta BIGINT NOT NULL,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    -- Foreign Keys
    CONSTRAINT fk_reclamos_cliente FOREIGN KEY (cod_cliente)
        REFERENCES personas(cod_persona) ON DELETE RESTRICT,
    CONSTRAINT fk_reclamos_orden_servicio FOREIGN KEY (orden_servicio_id)
        REFERENCES orden_servicios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reclamos_tipo FOREIGN KEY (cod_tipo_reclamo)
        REFERENCES tipo_reclamos(cod_tipo_reclamo) ON DELETE RESTRICT
);-- 4. Agregar registros a la tabla de migraciones
INSERT INTO migrations (migration, batch) VALUES
('2025_11_10_100001_create_tipo_reclamos_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations)),
('2025_11_10_100002_create_reclamos_table', (SELECT COALESCE(MAX(batch), 0) FROM migrations));

-- 5. Verificación
SELECT 'tipo_reclamos creada' AS tabla, COUNT(*) AS registros FROM tipo_reclamos
UNION ALL
SELECT 'reclamos creada' AS tabla, COUNT(*) AS registros FROM reclamos;
