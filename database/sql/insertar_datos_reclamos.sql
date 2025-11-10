-- Script para insertar datos de prueba en reclamos
-- Asegúrate de tener clientes, órdenes de servicio y tipos de reclamo creados

-- Reclamo 1: Prioridad Alta - Pendiente
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Falla de Repuesto' LIMIT 1),
    CURRENT_DATE - INTERVAL '2 days',
    'Alta',
    'El repuesto instalado (filtro de aceite) presenta fuga después de 2 días del servicio. El cliente solicita revisión urgente y está muy molesto con la situación.',
    'Pendiente',
    1,
    NOW() - INTERVAL '2 days',
    NOW() - INTERVAL '2 days',
    NOW() - INTERVAL '2 days'
);

-- Reclamo 2: Prioridad Media - En Proceso
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 1),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 1),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Demora en el Servicio' LIMIT 1),
    CURRENT_DATE - INTERVAL '5 days',
    'Media',
    'El servicio de mantenimiento programado para 3 horas tomó 2 días completos. Cliente no fue notificado del retraso y tuvo que llamar varias veces para consultar el estado.',
    'En Proceso',
    1,
    NOW() - INTERVAL '5 days',
    NOW() - INTERVAL '5 days',
    NOW() - INTERVAL '5 days'
);

-- Reclamo 3: Prioridad Alta - Resuelto
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    resolucion,
    fecha_resolucion,
    usuario_resolucion,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 2),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 2),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Calidad de Servicio' LIMIT 1),
    CURRENT_DATE - INTERVAL '10 days',
    'Alta',
    'Después del lavado del motor, el vehículo presenta problemas de arranque. Antes no tenía este inconveniente. El cliente requiere solución inmediata.',
    'Resuelto',
    'Se revisó completamente el sistema eléctrico. Se encontró que un conector se había aflojado durante el lavado. Se corrigió sin costo y se realizó prueba de funcionamiento de 2 horas. Cliente satisfecho.',
    CURRENT_DATE - INTERVAL '7 days',
    1,
    1,
    NOW() - INTERVAL '10 days',
    NOW() - INTERVAL '10 days',
    NOW() - INTERVAL '7 days'
);

-- Reclamo 4: Prioridad Media - Pendiente
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 3),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 3),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Atención al Cliente' LIMIT 1),
    CURRENT_DATE - INTERVAL '1 day',
    'Media',
    'El recepcionista fue descortés y no escuchó las inquietudes del cliente sobre el vehículo. Cliente solicita mejor trato en futuras visitas.',
    'Pendiente',
    1,
    NOW() - INTERVAL '1 day',
    NOW() - INTERVAL '1 day',
    NOW() - INTERVAL '1 day'
);

-- Reclamo 5: Prioridad Baja - Resuelto
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    resolucion,
    fecha_resolucion,
    usuario_resolucion,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 4),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 4),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Otros' LIMIT 1),
    CURRENT_DATE - INTERVAL '15 days',
    'Baja',
    'El radio quedó desprogramado y perdió todas las estaciones guardadas. Cliente solicita reprogramación.',
    'Resuelto',
    'Se programaron nuevamente todas las estaciones del radio sin costo. Cliente conforme.',
    CURRENT_DATE - INTERVAL '14 days',
    1,
    1,
    NOW() - INTERVAL '15 days',
    NOW() - INTERVAL '15 days',
    NOW() - INTERVAL '14 days'
);

-- Reclamo 6: Prioridad Alta - En Proceso
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 5),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 5),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Precio/Facturación' LIMIT 1),
    CURRENT_DATE - INTERVAL '3 days',
    'Alta',
    'El presupuesto inicial fue de Gs. 500.000 pero la factura final fue de Gs. 850.000 sin explicación previa. Cliente exige justificación detallada.',
    'En Proceso',
    1,
    NOW() - INTERVAL '3 days',
    NOW() - INTERVAL '3 days',
    NOW() - INTERVAL '3 days'
);

-- Reclamo 7: Prioridad Media - Cerrado
INSERT INTO reclamos (
    cod_cliente,
    orden_servicio_id,
    cod_tipo_reclamo,
    fecha_reclamo,
    prioridad,
    descripcion,
    estado,
    resolucion,
    fecha_resolucion,
    usuario_resolucion,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    (SELECT cod_persona FROM personas LIMIT 1 OFFSET 6),
    (SELECT id FROM orden_servicios WHERE estado_trabajo IN ('Finalizado', 'Facturado') LIMIT 1 OFFSET 6),
    (SELECT cod_tipo_reclamo FROM tipo_reclamos WHERE descripcion = 'Falla de Repuesto' LIMIT 1),
    CURRENT_DATE - INTERVAL '20 days',
    'Media',
    'Las pastillas de freno rechinán excesivamente al frenar. Cliente indica que no pasaba antes del cambio.',
    'Cerrado',
    'Se reemplazaron las pastillas por otras de mejor calidad sin costo adicional. Se realizó prueba de ruta de 30 km. No se detectó ruido. Cliente satisfecho. Caso cerrado.',
    CURRENT_DATE - INTERVAL '17 days',
    1,
    1,
    NOW() - INTERVAL '20 days',
    NOW() - INTERVAL '20 days',
    NOW() - INTERVAL '17 days'
);

-- Verificar reclamos insertados
SELECT
    r.cod_reclamo,
    CASE
        WHEN p.razon_social IS NOT NULL THEN p.razon_social
        ELSE CONCAT(p.nombres, ' ', p.apellidos)
    END as cliente,
    r.fecha_reclamo,
    t.descripcion as tipo,
    r.prioridad,
    r.estado
FROM reclamos r
JOIN personas p ON r.cod_cliente = p.cod_persona
JOIN tipo_reclamos t ON r.cod_tipo_reclamo = t.cod_tipo_reclamo
ORDER BY r.fecha_reclamo DESC;
