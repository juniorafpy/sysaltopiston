# Reclamos

## Descripción Básica

Este caso de uso permite a un usuario autorizado registrar, gestionar y resolver reclamos de clientes relacionados con órdenes de servicio finalizadas o facturadas. El sistema valida automáticamente que solo se puedan asociar reclamos a órdenes completadas, gestiona el flujo de estados desde registro hasta resolución, y notifica automáticamente cuando se registran reclamos de alta prioridad.

## Actores Relacionados

-   **Recepcionista**: Registra los reclamos de los clientes
-   **Jefe de Servicio**: Gestiona, procesa y resuelve los reclamos
-   **Administrador**: Acceso completo al módulo

## Pre Condición

-   Poseer perfil de usuario con permisos para gestionar reclamos
-   Conexión a base de datos
-   Debe existir un Cliente registrado en la tabla `personas`
-   Debe existir una Orden de Servicio con estado "Finalizado" o "Facturado"
-   Deben existir Tipos de Reclamo activos en el catálogo
-   El usuario debe tener una sesión activa en el sistema
-   Opcionalmente, el usuario puede tener asignada una Sucursal (cod_sucursal)

## Tablas de Base de Datos Relacionadas

### tipo_reclamos

Tabla catálogo que almacena los tipos de reclamo disponibles en el sistema.

**Campos principales:**

-   `cod_tipo_reclamo`: Primary key (BIGSERIAL)
-   `descripcion`: Descripción del tipo de reclamo (VARCHAR 100, NOT NULL)
-   `activo`: Indica si el tipo está activo (BOOLEAN, DEFAULT TRUE)
-   `created_at`: Timestamp de creación
-   `updated_at`: Timestamp de última modificación

**Registros iniciales del sistema:**

-   Falla de Repuesto
-   Demora en el Servicio
-   Calidad de Servicio
-   Atención al Cliente
-   Precio/Facturación
-   Otros

### reclamos

Tabla principal que almacena la información completa de cada reclamo registrado.

**Campos principales:**

-   `cod_reclamo`: Primary key (BIGSERIAL)
-   `cod_cliente`: FK a personas.cod_persona (NOT NULL) - Cliente que presenta el reclamo
-   `orden_servicio_id`: FK a orden_servicios.id (NOT NULL) - Orden de servicio relacionada
-   `cod_tipo_reclamo`: FK a tipo_reclamos.cod_tipo_reclamo (NOT NULL) - Tipo de reclamo
-   `fecha_reclamo`: Fecha del reclamo (DATE, NOT NULL)
-   `prioridad`: Prioridad del reclamo (ENUM: 'Alta', 'Media', 'Baja', DEFAULT 'Media')
-   `descripcion`: Descripción detallada del reclamo (TEXT, NOT NULL, MAX 1000 chars)
-   `estado`: Estado actual (ENUM: 'Pendiente', 'En Proceso', 'Resuelto', 'Cerrado', DEFAULT 'Pendiente')
-   `resolucion`: Descripción de la resolución aplicada (TEXT, NULLABLE, MAX 1000 chars)
-   `fecha_resolucion`: Fecha en que se resolvió (DATE, NULLABLE)
-   `usuario_resolucion`: FK a users - Usuario que resolvió (BIGINT, NULLABLE)
-   `cod_sucursal`: FK a sucursal (BIGINT, NULLABLE) - Sucursal donde se registró
-   `usuario_alta`: Usuario que registró el reclamo (BIGINT, NOT NULL)
-   `fecha_alta`: Timestamp de registro (DEFAULT CURRENT_TIMESTAMP)
-   `created_at`: Timestamp de creación
-   `updated_at`: Timestamp de última modificación

**Constraints:**

-   FK `cod_cliente` → `personas(cod_persona)` ON DELETE RESTRICT
-   FK `orden_servicio_id` → `orden_servicios(id)` ON DELETE RESTRICT
-   FK `cod_tipo_reclamo` → `tipo_reclamos(cod_tipo_reclamo)` ON DELETE RESTRICT

---

## Flujo de Eventos

### Flujo Básico

**Listado de Reclamos**

1. El usuario selecciona en el menú el grupo "Servicios"
2. El usuario hace clic en el ítem "Reclamos"
3. El sistema abre la interfaz Reclamos, que muestra los reclamos ya registrados
4. El sistema llama conexión
5. El sistema consulta los datos de la tabla `reclamos` y sus tablas relacionadas:

```sql
SELECT
    r.cod_reclamo,
    r.fecha_reclamo,
    r.prioridad,
    r.estado,
    r.descripcion,
    p.nombres,
    p.apellidos,
    p.razon_social,
    p.nro_documento,
    os.id AS orden_servicio_id,
    v.matricula,
    tr.descripcion AS tipo_reclamo,
    u.name AS usuario_registro
FROM reclamos r
INNER JOIN personas p ON r.cod_cliente = p.cod_persona
INNER JOIN orden_servicios os ON r.orden_servicio_id = os.id
INNER JOIN recepcion_vehiculos rv ON os.recepcion_vehiculo_id = rv.id
INNER JOIN vehiculos v ON rv.vehiculo_id = v.id
INNER JOIN tipo_reclamos tr ON r.cod_tipo_reclamo = tr.cod_tipo_reclamo
LEFT JOIN users u ON r.usuario_alta = u.id
ORDER BY r.fecha_reclamo DESC;
```

6. El sistema agrega los datos a la grilla con las columnas:
    - Nº (cod_reclamo)
    - Fecha (fecha_reclamo en formato dd/mm/yyyy)
    - Cliente (nombres + apellidos o razón_social, limitado a 30 caracteres)
    - Tipo (descripción del tipo_reclamo, limitado a 20 caracteres)
    - Estado (badge con colores: Rojo=Pendiente, Amarillo=En Proceso, Verde=Resuelto, Gris=Cerrado)
    - Registrado por (nombre del usuario)
7. El sistema calcula el contador de reclamos pendientes:

```sql
SELECT COUNT(*) FROM reclamos WHERE estado = 'Pendiente';
```

8. El sistema actualiza el badge del menú con el contador y color según regla:
    - Verde: 0 pendientes
    - Amarillo: 1-5 pendientes
    - Rojo: más de 5 pendientes

### Crear Reclamo

9. El Recepcionista está en la pantalla Listado de Reclamos
10. El Recepcionista presiona el botón "Nuevo Reclamo"
11. El sistema redirecciona a la interfaz Crear Reclamo
12. El sistema llama conexión
13. El sistema recupera datos del sistema automáticamente:
    - usuario_alta = Auth::id()
    - fecha_alta = now()
    - cod_sucursal = Auth::user()->cod_sucursal ?? null

```php
$data['usuario_alta'] = Auth::id();
$data['fecha_alta'] = now();
$data['cod_sucursal'] = Auth::user()->cod_sucursal ?? null;
```

14. El sistema muestra el formulario con 2 secciones principales:

**Sección 1: Información del Reclamo**

-   Campo "Cliente" (Select vacío, obligatorio)
-   Campo "Orden de Servicio (OS) Ref." (Select deshabilitado hasta seleccionar cliente)
-   Campo "Vehículo/Chapa" (Placeholder de solo lectura)

**Sección 2: Detalles del Reclamo**

-   Campo "Fecha del Reclamo" (DatePicker precargado con fecha actual, máximo hoy)
-   Campo "Tipo de Reclamo" (Select con tipos activos, obligatorio)
-   Campo "Prioridad" (Select con opciones Alta/Media/Baja, default: Media)
-   Campo "Descripción Detallada" (Textarea, máximo 1000 caracteres, obligatorio)

**Selección del Cliente**

15. El Recepcionista hace clic en el campo "Cliente"
16. El sistema llama conexión
17. El sistema permite búsqueda dinámica en la tabla `personas`:

```sql
SELECT
    cod_persona,
    nombres,
    apellidos,
    razon_social,
    nro_documento
FROM personas
WHERE (nombres ILIKE '%{search}%'
   OR apellidos ILIKE '%{search}%'
   OR razon_social ILIKE '%{search}%'
   OR nro_documento ILIKE '%{search}%')
  AND activo = true
ORDER BY
    CASE
        WHEN razon_social IS NOT NULL THEN razon_social
        ELSE nombres || ' ' || apellidos
    END
LIMIT 50;
```

18. El Recepcionista escribe para buscar (por nombre, apellido, razón social o documento)
19. El sistema muestra resultados formateados:
    -   Personas físicas: "Juan Pérez"
    -   Personas jurídicas: "Automotores SA"
20. El Recepcionista selecciona un Cliente
21. El sistema guarda el cod_cliente seleccionado
22. El sistema habilita el campo "Orden de Servicio (OS) Ref."
23. El sistema limpia cualquier OS previamente seleccionada
24. El sistema limpia el campo "Vehículo/Chapa"

**Selección de Orden de Servicio**

25. El Recepcionista hace clic en el campo "Orden de Servicio (OS) Ref."
26. El sistema llama conexión
27. El sistema consulta órdenes elegibles para reclamo:

```sql
SELECT
    os.id,
    os.fecha_inicio,
    os.estado_trabajo,
    v.matricula,
    CONCAT('OS #', os.id, ' - ', v.matricula, ' (',
           TO_CHAR(os.fecha_inicio, 'DD/MM/YYYY'), ')') AS display_text
FROM orden_servicios os
INNER JOIN recepcion_vehiculos rv ON os.recepcion_vehiculo_id = rv.id
INNER JOIN vehiculos v ON rv.vehiculo_id = v.id
WHERE rv.cod_cliente = {cod_cliente_seleccionado}
  AND os.estado_trabajo IN ('Finalizado', 'Facturado')
ORDER BY os.fecha_inicio DESC
LIMIT 100;
```

**Código PHP para cargar el combo:**

```php
$ordenes = OrdenServicio::query()
    ->join('recepcion_vehiculos', 'orden_servicios.recepcion_vehiculo_id', '=', 'recepcion_vehiculos.id')
    ->join('vehiculos', 'recepcion_vehiculos.vehiculo_id', '=', 'vehiculos.id')
    ->where('recepcion_vehiculos.cod_cliente', $clienteId)
    ->whereIn('orden_servicios.estado_trabajo', ['Finalizado', 'Facturado'])
    ->select([
        'orden_servicios.id',
        'orden_servicios.fecha_inicio',
        'vehiculos.matricula'
    ])
    ->orderBy('orden_servicios.fecha_inicio', 'desc')
    ->limit(100)
    ->get()
    ->mapWithKeys(fn($os) => [
        $os->id => "OS #{$os->id} - {$os->matricula} (" .
                   $os->fecha_inicio->format('d/m/Y') . ")"
    ]);
```

28. El sistema carga el combo con formato: "OS #1234 - ABC123 (05/11/2025)"
29. El sistema muestra texto de ayuda: "Solo órdenes Finalizadas o Facturadas"
30. SI NO hay órdenes elegibles:
    -   El sistema muestra notificación: "Este cliente no tiene órdenes finalizadas o facturadas"
    -   El campo queda vacío
31. El Recepcionista selecciona una Orden de Servicio
32. El sistema guarda el orden_servicio_id seleccionado
33. El sistema llama conexión
34. El sistema consulta la matrícula del vehículo:

```sql
SELECT v.matricula
FROM vehiculos v
INNER JOIN recepcion_vehiculos rv ON rv.vehiculo_id = v.id
INNER JOIN orden_servicios os ON os.recepcion_vehiculo_id = rv.id
WHERE os.id = {orden_servicio_id};
```

35. El sistema actualiza el campo "Vehículo/Chapa" con la matrícula encontrada
36. SI NO se encuentra vehículo, el sistema muestra "N/A"

**Completar Detalles del Reclamo**

37. El sistema precarga automáticamente la "Fecha del Reclamo" con fecha actual
38. El Recepcionista verifica o modifica la fecha usando el calendario
39. El sistema valida que la fecha no sea futura (máximo: hoy)
40. El Recepcionista hace clic en el campo "Tipo de Reclamo"
41. El sistema llama conexión
42. El sistema consulta tipos de reclamo activos:

```sql
SELECT cod_tipo_reclamo, descripcion
FROM tipo_reclamos
WHERE activo = true
ORDER BY descripcion;
```

43. El sistema carga el combo con las opciones:
    -   Falla de Repuesto
    -   Demora en el Servicio
    -   Calidad de Servicio
    -   Atención al Cliente
    -   Precio/Facturación
    -   Otros
44. El Recepcionista selecciona el Tipo de Reclamo
45. El sistema guarda el cod_tipo_reclamo seleccionado
46. El Recepcionista hace clic en el campo "Prioridad"
47. El sistema muestra tres opciones:
    -   Alta (para casos urgentes que requieren atención inmediata)
    -   Media (valor por defecto, para casos normales)
    -   Baja (para casos de menor urgencia)
48. El Recepcionista selecciona la Prioridad según la gravedad del reclamo
49. SI selecciona "Alta":
    -   El sistema marca internamente flag de alta prioridad para notificación posterior
50. El Recepcionista hace clic en el área de texto "Descripción Detallada"
51. El sistema muestra el texto de ayuda: "Describa detalladamente el motivo del reclamo"
52. El Recepcionista escribe la descripción completa del reclamo
53. El sistema muestra contador de caracteres: "X/1000"
54. El sistema valida en tiempo real que no exceda 1000 caracteres

**Guardar el Reclamo**

55. El Recepcionista revisa todos los datos ingresados
56. El Recepcionista presiona el botón "Crear" en la parte inferior
57. El sistema valida que todos los campos obligatorios (\*) estén completos:
    -   Cliente seleccionado
    -   Orden de Servicio seleccionada
    -   Fecha del reclamo ingresada
    -   Tipo de reclamo seleccionado
    -   Prioridad seleccionada
    -   Descripción ingresada (mínimo 10 caracteres)
58. SI falta algún campo obligatorio:
    -   El sistema muestra mensaje de error en rojo debajo del campo faltante: "Este campo es obligatorio"
    -   El sistema previene el guardado
    -   El sistema mantiene los datos ya ingresados
    -   El Recepcionista debe completar los campos faltantes
    -   **[Volver al paso 56]**
59. SI todos los campos son válidos, el sistema llama conexión
60. El sistema prepara los datos para inserción:

```php
$data = [
    'cod_cliente' => $cod_cliente,
    'orden_servicio_id' => $orden_servicio_id,
    'cod_tipo_reclamo' => $cod_tipo_reclamo,
    'fecha_reclamo' => $fecha_reclamo,
    'prioridad' => $prioridad,  // 'Alta', 'Media', 'Baja'
    'descripcion' => $descripcion,
    'estado' => 'Pendiente',  // Estado inicial por defecto
    'usuario_alta' => Auth::id(),
    'fecha_alta' => now(),
    'cod_sucursal' => Auth::user()->cod_sucursal ?? null,
    'created_at' => now(),
    'updated_at' => now()
];
```

61. El sistema ejecuta INSERT en la tabla `reclamos`:

```sql
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
    cod_sucursal,
    created_at,
    updated_at
) VALUES (
    {cod_cliente},
    {orden_servicio_id},
    {cod_tipo_reclamo},
    '{fecha_reclamo}',
    '{prioridad}',
    '{descripcion}',
    'Pendiente',
    {usuario_alta},
    NOW(),
    {cod_sucursal},
    NOW(),
    NOW()
) RETURNING cod_reclamo;
```

62. La base de datos genera automáticamente el cod_reclamo (BIGSERIAL)
63. El sistema recibe el cod_reclamo asignado
64. El sistema verifica si la prioridad es "Alta":

```php
if ($data['prioridad'] === 'Alta') {
    // Notificación persistente
    Notification::make()
        ->warning()
        ->title('Reclamo de Alta Prioridad Registrado')
        ->body("El reclamo #{$cod_reclamo} ha sido registrado con prioridad ALTA. Se notificará al Jefe de Servicio.")
        ->persistent()
        ->send();

    // [FUTURO] Enviar email al Jefe de Servicio
    // Mail::to($jefeServicio->email)
    //     ->send(new ReclamoAltaPrioridadMail($reclamo));
}
```

65. SI prioridad = 'Alta':
    -   El sistema muestra notificación amarilla persistente
    -   Título: "Reclamo de Alta Prioridad Registrado"
    -   Mensaje: "El reclamo #{cod_reclamo} ha sido registrado con prioridad ALTA. Se notificará al Jefe de Servicio."
    -   La notificación requiere cierre manual por el usuario
    -   **[FUTURO]** El sistema enviará email/notificación push al Jefe de Servicio
66. El sistema muestra notificación de éxito:
    -   Tipo: Success (verde)
    -   Título: "Reclamo Registrado Exitosamente"
    -   Mensaje: "Reclamo #{cod_reclamo} registrado correctamente."
    -   Duración: 5 segundos (desaparece automáticamente)
67. El sistema redirige automáticamente al "Listado de Reclamos"
68. El sistema recarga la grilla mostrando el nuevo reclamo en la primera fila (ordenado por fecha descendente)
69. El sistema actualiza el badge del menú:
    -   Incrementa el contador de pendientes en 1
    -   Ajusta el color según la nueva cantidad (verde/amarillo/rojo)

### Ver Detalles de un Reclamo

70. Desde el listado, el Recepcionista hace clic en el botón de acciones (⋮) de un reclamo
71. El sistema muestra el menú desplegable (ActionGroup) con las opciones disponibles
72. El Recepcionista selecciona "Ver"
73. El sistema llama conexión
74. El sistema consulta los datos completos del reclamo:

```sql
SELECT
    r.*,
    p.nombres AS cliente_nombres,
    p.apellidos AS cliente_apellidos,
    p.razon_social AS cliente_razon_social,
    p.nro_documento AS cliente_documento,
    os.id AS orden_servicio_numero,
    os.fecha_inicio AS orden_fecha_inicio,
    v.matricula AS vehiculo_matricula,
    v.marca,
    v.modelo,
    tr.descripcion AS tipo_descripcion,
    u_alta.name AS usuario_alta_nombre,
    u_resol.name AS usuario_resolucion_nombre
FROM reclamos r
INNER JOIN personas p ON r.cod_cliente = p.cod_persona
INNER JOIN orden_servicios os ON r.orden_servicio_id = os.id
INNER JOIN recepcion_vehiculos rv ON os.recepcion_vehiculo_id = rv.id
INNER JOIN vehiculos v ON rv.vehiculo_id = v.id
INNER JOIN tipo_reclamos tr ON r.cod_tipo_reclamo = tr.cod_tipo_reclamo
LEFT JOIN users u_alta ON r.usuario_alta = u_alta.id
LEFT JOIN users u_resol ON r.usuario_resolucion = u_resol.id
WHERE r.cod_reclamo = {cod_reclamo};
```

75. El sistema abre la vista detallada del reclamo (modo solo lectura)
76. El sistema muestra todas las secciones:

**Sección 1: Información del Reclamo**

-   Cliente: [Nombre completo o razón social]
-   OS Ref.: OS #[número]
-   Vehículo/Chapa: [Matrícula]

**Sección 2: Detalles del Reclamo**

-   Fecha: [dd/mm/yyyy]
-   Tipo: [Descripción del tipo]
-   Prioridad: [Badge con color según prioridad]
-   Descripción: [Texto completo]

**Sección 3: Estado y Resolución**

-   Estado: [Badge con color según estado]
-   Fecha de Resolución: [dd/mm/yyyy] (solo si está resuelto/cerrado)
-   Resolución: [Texto completo] (solo si está resuelto/cerrado)

**Sección 4: Auditoría** (solo lectura)

-   Usuario que registró: [Nombre del usuario]
-   Fecha de registro: [dd/mm/yyyy HH:mm]
-   Sucursal: [Nombre de la sucursal] (si aplica)

77. El sistema muestra botones de acción en el encabezado:
    -   Botón "Editar" (permite modificar el reclamo)
    -   Botón "Eliminar" (permite borrar el reclamo con confirmación)
78. El Recepcionista puede hacer clic en "Atrás" para volver al listado

### Editar un Reclamo

79. Desde el listado, el Recepcionista hace clic en el botón de acciones (⋮)
80. El Recepcionista selecciona "Editar"
81. El sistema llama conexión
82. El sistema consulta los datos actuales del reclamo (misma query del paso 74)
83. El sistema abre el formulario de edición con todos los datos precargados
84. El sistema muestra todas las secciones (incluyendo "Estado y Resolución")
85. El sistema permite modificar todos los campos EXCEPTO:
    -   Usuario Alta (solo lectura en Sección Auditoría)
    -   Fecha Alta (solo lectura en Sección Auditoría)
    -   Sucursal (solo lectura en Sección Auditoría)
86. El Recepcionista puede modificar:
    -   Cliente
    -   Orden de Servicio (se recalcula según el nuevo cliente si cambia)
    -   Fecha del Reclamo
    -   Tipo de Reclamo
    -   Prioridad
    -   Descripción
    -   **Estado** (Pendiente, En Proceso, Resuelto, Cerrado)
    -   Fecha de Resolución (solo si estado = Resuelto o Cerrado)
    -   Resolución (textarea, solo si estado = Resuelto o Cerrado)
87. SI el Recepcionista cambia el estado a "Resuelto" o "Cerrado":
    -   El sistema muestra los campos "Fecha de Resolución" y "Resolución"
    -   El sistema hace obligatorio el campo "Resolución"
88. SI el estado es "Resuelto" o "Cerrado" y el campo "Resolución" está vacío:
    -   El sistema muestra error: "La resolución es obligatoria para estados Resuelto/Cerrado"
    -   El sistema previene el guardado
    -   **[Volver al paso 86]**
89. El Recepcionista hace clic en el botón "Guardar"
90. El sistema valida todos los campos obligatorios
91. El sistema prepara los datos para actualización:

```php
$data = [
    'cod_cliente' => $cod_cliente,
    'orden_servicio_id' => $orden_servicio_id,
    'cod_tipo_reclamo' => $cod_tipo_reclamo,
    'fecha_reclamo' => $fecha_reclamo,
    'prioridad' => $prioridad,
    'descripcion' => $descripcion,
    'estado' => $estado,
    'updated_at' => now()
];

// Si el estado cambió a Resuelto/Cerrado, registrar datos de resolución
if (in_array($estado, ['Resuelto', 'Cerrado']) && !$fecha_resolucion) {
    $data['resolucion'] = $resolucion;
    $data['fecha_resolucion'] = now();
    $data['usuario_resolucion'] = Auth::id();
}
```

92. El sistema llama conexión
93. El sistema ejecuta UPDATE en la tabla `reclamos`:

```sql
UPDATE reclamos SET
    cod_cliente = {cod_cliente},
    orden_servicio_id = {orden_servicio_id},
    cod_tipo_reclamo = {cod_tipo_reclamo},
    fecha_reclamo = '{fecha_reclamo}',
    prioridad = '{prioridad}',
    descripcion = '{descripcion}',
    estado = '{estado}',
    resolucion = '{resolucion}',  -- si aplica
    fecha_resolucion = '{fecha_resolucion}',  -- si aplica
    usuario_resolucion = {usuario_resolucion},  -- si aplica
    updated_at = NOW()
WHERE cod_reclamo = {cod_reclamo};
```

94. El sistema muestra notificación de éxito: "Reclamo actualizado correctamente" (verde, 5 segundos)
95. El sistema redirige al "Listado de Reclamos"
96. El sistema actualiza la grilla mostrando los datos modificados

### Marcar Reclamo como Resuelto (Acción Rápida)

97. Desde el listado, el Recepcionista hace clic en el botón de acciones (⋮) de un reclamo
98. El sistema verifica el estado actual del reclamo
99. SI estado = 'Resuelto' o 'Cerrado':
    -   El sistema NO muestra la opción "Marcar Resuelto" en el menú (hidden: true)
100.    SI estado = 'Pendiente' o 'En Proceso':


    - El sistema muestra la opción "Marcar Resuelto" con ícono ✓ (color verde)

101. El Recepcionista selecciona "Marcar Resuelto"
102. El sistema abre un modal/dialog emergente con:


    - Título: "Marcar como Resuelto"
    - Campo "Resolución" (Textarea obligatoria, máximo 1000 caracteres)
    - Contador de caracteres: "X/1000"
    - Botón "Guardar" (color verde)
    - Botón "Cancelar" (color gris)

103. El Recepcionista escribe la descripción de la resolución aplicada
104. El Recepcionista hace clic en "Guardar"
105. El sistema valida que el campo "Resolución" no esté vacío
106. SI está vacío:


    - El sistema muestra error: "La resolución es obligatoria"
    - El sistema mantiene el modal abierto
    - **[Volver al paso 103]**

107. El sistema llama conexión
108. El sistema ejecuta UPDATE:

```sql
UPDATE reclamos SET
    estado = 'Resuelto',
    resolucion = '{texto_ingresado}',
    fecha_resolucion = CURRENT_DATE,
    usuario_resolucion = {user_id},
    updated_at = NOW()
WHERE cod_reclamo = {cod_reclamo};
```

```php
Reclamo::where('cod_reclamo', $cod_reclamo)->update([
    'estado' => 'Resuelto',
    'resolucion' => $resolucion,
    'fecha_resolucion' => now(),
    'usuario_resolucion' => Auth::id(),
    'updated_at' => now()
]);
```

109. El sistema cierra el modal automáticamente
110. El sistema muestra notificación de éxito: "Reclamo resuelto exitosamente" (verde, 5 segundos)
111. El sistema actualiza la fila en la grilla **sin recargar toda la página**:


    - Badge de Estado cambia a "Resuelto" (color verde)
    - Se actualiza la fecha de modificación
    - La opción "Marcar Resuelto" desaparece del menú de acciones

112. El sistema actualiza el badge del menú:


    - Decrementa el contador de pendientes en 1
    - Ajusta el color según la nueva cantidad (verde/amarillo/rojo)

### Eliminar un Reclamo

113. Desde el listado, el Recepcionista hace clic en el botón de acciones (⋮)
114. El Recepcionista selecciona "Eliminar"
115. El sistema muestra dialog de confirmación:


    - Título: "¿Está seguro?"
    - Mensaje: "Esta acción no se puede deshacer"
    - Botón "Confirmar" (color rojo)
    - Botón "Cancelar" (color gris)

116. SI el Recepcionista hace clic en "Cancelar":


    - El sistema cierra el dialog
    - No se elimina ningún registro
    - **[FIN DE FLUJO]**

117. SI el Recepcionista hace clic en "Confirmar":
118. El sistema llama conexión
119. El sistema ejecuta DELETE:

```sql
DELETE FROM reclamos WHERE cod_reclamo = {cod_reclamo};
```

```php
Reclamo::where('cod_reclamo', $cod_reclamo)->delete();
```

120. El sistema recibe confirmación de eliminación exitosa
121. El sistema muestra notificación de éxito: "Reclamo eliminado" (verde, 3 segundos)
122. El sistema remueve la fila de la grilla **sin recargar toda la página**
123. SI el reclamo eliminado estaba en estado "Pendiente":


    - El sistema actualiza el badge del menú
    - Decrementa el contador de pendientes
    - Ajusta el color según la nueva cantidad

124. **[FIN DE FLUJO]**

---

### Paso 5: Formulario de Registro

El sistema abre el formulario "Registrar Reclamo" con las siguientes secciones:

**Sección 1: Información del Reclamo**

-   Campo "Cliente" (vacío)
-   Campo "Orden de Servicio (OS) Ref." (deshabilitado hasta seleccionar cliente)
-   Campo "Vehículo/Chapa" (solo lectura, muestra placeholder)

**Sección 2: Detalles del Reclamo**

-   Campo "Fecha del Reclamo" (precargado con fecha actual)
-   Campo "Tipo de Reclamo" (combo con opciones)
-   Campo "Prioridad" (combo con opciones: Alta/Media/Baja)
-   Campo "Descripción Detallada" (área de texto)

### Paso 6: Carga de Datos del Sistema

El sistema automáticamente recupera:

-   Sucursal del usuario logueado
-   ID del usuario (para usuario_alta)
-   Fecha y hora actual (para fecha_alta)

### Paso 7: Selección del Cliente

El Recepcionista hace clic en el combo "Cliente".

### Paso 8: Búsqueda de Cliente

El sistema:

-   Carga la lista de clientes activos
-   Permite buscar por nombre completo o número de documento
-   Muestra coincidencias en tiempo real mientras se escribe
-   Limita los resultados a 50 registros

### Paso 9: Cliente Seleccionado

El Recepcionista selecciona un Cliente del combo desplegable.

### Paso 10: Activación del Combo OS

El sistema:

-   Habilita el campo "Orden de Servicio (OS) Ref."
-   Limpia cualquier selección previa de OS
-   Limpia el campo de Vehículo

### Paso 11: Carga de Órdenes Filtradas

**[ACCIÓN CLAVE]** El sistema realiza una consulta a la base de datos para cargar el combo "Orden de Servicio (OS) Ref." con las siguientes condiciones:

```sql
SELECT
    os.id,
    os.fecha_inicio,
    v.matricula,
    os.estado_trabajo
FROM orden_servicios os
JOIN recepcion_vehiculos rv ON os.recepcion_vehiculo_id = rv.id
JOIN vehiculos v ON rv.vehiculo_id = v.id
WHERE rv.cod_cliente = {cod_cliente_seleccionado}
  AND os.estado_trabajo IN ('Finalizado', 'Facturado')
ORDER BY os.fecha_inicio DESC
LIMIT 100;
```

**Código PHP para preparar las opciones:**

```php
$ordenes = OrdenServicio::query()
    ->join('recepcion_vehiculos', 'orden_servicios.recepcion_vehiculo_id', '=', 'recepcion_vehiculos.id')
    ->join('vehiculos', 'recepcion_vehiculos.vehiculo_id', '=', 'vehiculos.id')
    ->where('recepcion_vehiculos.cod_cliente', $clienteId)
    ->whereIn('orden_servicios.estado_trabajo', ['Finalizado', 'Facturado'])
    ->select([
        'orden_servicios.id',
        'orden_servicios.fecha_inicio',
        'vehiculos.matricula'
    ])
    ->orderBy('orden_servicios.fecha_inicio', 'desc')
    ->limit(100)
    ->get()
    ->mapWithKeys(fn($os) => [
        $os->id => "OS #{$os->id} - {$os->matricula} (" . $os->fecha_inicio->format('d/m/Y') . ")"
    ]);
```

-   Formato de visualización: "OS #[número] - [matrícula] ([fecha])"
-   Ejemplo: "OS #1234 - ABC123 (05/11/2025)"

### Paso 12: Ayuda Contextual

El sistema muestra texto de ayuda debajo del combo: "Solo órdenes Finalizadas o Facturadas".

### Paso 13: Selección de Orden de Servicio

El Recepcionista selecciona la Orden de Servicio (OS) Ref. correspondiente al reclamo.

### Paso 14: Carga Automática del Vehículo

**[ACCIÓN CLAVE]** El sistema automáticamente:

-   Busca el vehículo asociado a la OS seleccionada
-   Extrae la matrícula/chapa del vehículo
-   Actualiza el campo de solo lectura "Vehículo/Chapa" con la matrícula
-   Si no hay vehículo, muestra "N/A"

### Paso 15: Verificación de Fecha

El Recepcionista verifica la "Fecha del Reclamo". El sistema tiene precargada la fecha actual.

### Paso 16: Ajuste de Fecha (Opcional)

Si es necesario, el Recepcionista puede modificar la fecha usando el calendario emergente.

### Paso 17: Validación de Fecha

El sistema valida que la fecha seleccionada no sea futura (máximo: hoy).

### Paso 18: Selección del Tipo de Reclamo

El Recepcionista hace clic en el combo "Tipo de Reclamo".

### Paso 19: Opciones de Tipo

El sistema muestra las opciones disponibles:

-   Falla de Repuesto
-   Demora en el Servicio
-   Calidad de Servicio
-   Atención al Cliente
-   Precio/Facturación
-   Otros

### Paso 20: Tipo Seleccionado

El Recepcionista selecciona el tipo que corresponde al reclamo.

### Paso 21: Selección de Prioridad

El Recepcionista hace clic en el combo "Prioridad".

### Paso 22: Opciones de Prioridad

El sistema muestra tres opciones:

-   Alta (para casos urgentes)
-   Media (valor por defecto)
-   Baja

### Paso 23: Prioridad Seleccionada

El Recepcionista selecciona la prioridad adecuada según la gravedad del reclamo.

### Paso 24: Indicador Visual de Prioridad

Si selecciona "Alta", el sistema resalta visualmente el campo (puede cambiar el color).

### Paso 25: Escritura de Descripción

El Recepcionista hace clic en el área de texto "Descripción Detallada del Reclamo".

### Paso 26: Texto de Ayuda

El sistema muestra texto de ayuda: "Describa detalladamente el motivo del reclamo".

### Paso 27: Ingreso de Descripción

El Recepcionista escribe la descripción completa del reclamo (máximo 1000 caracteres).

### Paso 28: Contador de Caracteres

El sistema muestra un contador indicando caracteres restantes.

### Paso 29: Revisión del Formulario

El Recepcionista revisa todos los datos ingresados:

-   Cliente seleccionado
-   OS de referencia
-   Vehículo mostrado
-   Fecha correcta
-   Tipo adecuado
-   Prioridad apropiada
-   Descripción completa

### Paso 30: Guardar Reclamo

El Recepcionista presiona el botón "Guardar Reclamo" ubicado en la parte inferior del formulario.

### Paso 31: Validación de Campos Obligatorios

El sistema valida que todos los campos marcados con asterisco (\*) estén completos:

-   Cliente (\*)
-   Orden de Servicio (\*)
-   Fecha del Reclamo (\*)
-   Tipo de Reclamo (\*)
-   Prioridad (\*)
-   Descripción Detallada (\*)

### Paso 32: Mensajes de Error (Si aplica)

Si falta algún campo obligatorio, el sistema:

-   Muestra un mensaje de error en rojo debajo del campo
-   Previene el guardado
-   Mantiene los datos ya ingresados
-   El Recepcionista debe completar los campos faltantes y volver al Paso 30

### Paso 33: Preparación de Datos

Si todos los campos son válidos, el sistema prepara los datos para inserción:

-   `cod_cliente`: ID del cliente seleccionado
-   `cod_orden_servicio`: ID de la OS seleccionada
-   `cod_tipo_reclamo`: ID del tipo seleccionado
-   `fecha_reclamo`: Fecha ingresada
-   `prioridad`: Valor seleccionado
-   `descripcion`: Texto ingresado
-   `estado`: "Pendiente" (por defecto)
-   `cod_sucursal`: Sucursal del usuario
-   `usuario_alta`: ID del usuario logueado
-   `fecha_alta`: Timestamp actual

### Paso 34: Conexión a Base de Datos

El sistema establece conexión con la base de datos PostgreSQL.

### Paso 35: Inserción de Registro

El sistema ejecuta la inserción en la tabla `reclamos` con todos los datos preparados.

### Paso 36: Generación de Código

La base de datos genera automáticamente el `cod_reclamo` (ID autoincremental).

### Paso 37: Confirmación de Inserción

El sistema recibe confirmación de que el registro fue insertado exitosamente.

### Paso 38: Verificación de Prioridad Alta

**[ACCIÓN CLAVE]** El sistema verifica si la prioridad del reclamo es "Alta".

### Paso 39: Notificación de Alta Prioridad (Condicional)

Si la prioridad es "Alta":

-   El sistema muestra una notificación amarilla persistente
-   Título: "Reclamo de Alta Prioridad Registrado"
-   Mensaje: "El reclamo #[número] ha sido registrado con prioridad ALTA. Se notificará al Jefe de Servicio."
-   **[FUTURO]** El sistema puede enviar email/notificación push al Jefe de Servicio

### Paso 40: Notificación de Éxito

El sistema muestra un mensaje de éxito en verde:

-   Título: "Reclamo Registrado Exitosamente"
-   Mensaje: "Reclamo #[número] registrado correctamente."
-   Duración: 5 segundos

### Paso 41: Redirección al Listado

El sistema automáticamente redirige al usuario a la vista "Listado de Reclamos".

### Paso 42: Actualización del Listado

El sistema muestra el listado actualizado con el nuevo reclamo en la primera fila (ordenado por fecha descendente).

### Paso 43: Badge Actualizado

El badge en el menú "Reclamos" se actualiza incrementando el contador de reclamos pendientes.

### Paso 44: Color del Badge

El sistema determina el color del badge según cantidad de pendientes:

-   Verde: 0 pendientes
-   Amarillo: 1-5 pendientes
-   Rojo: Más de 5 pendientes

### Paso 45: Fin del Proceso

El proceso de registro de reclamo finaliza exitosamente. El Recepcionista puede ver el reclamo registrado en la tabla.

---

## 3. Flujo Alternativo - Ver Detalles del Reclamo

### Paso 46: Visualización de Reclamo

Desde el listado, el Recepcionista puede hacer clic en el ícono "Ojo" (Ver) de cualquier reclamo.

### Paso 47: Pantalla de Detalle

El sistema abre una vista de solo lectura mostrando:

-   Toda la información del reclamo
-   Sección adicional "Estado y Resolución" (si está resuelto)
-   Sección "Auditoría" con: Usuario que registró, Fecha de registro, Sucursal

### Paso 48: Acciones Disponibles

El sistema muestra botones:

-   "Editar" (para modificar el reclamo)
-   "Eliminar" (para borrar el reclamo)
-   "Volver" (para regresar al listado)

---

## 4. Flujo Alternativo - Marcar Reclamo como Resuelto

### Paso 49: Acción Rápida

Desde el listado, el Recepcionista puede hacer clic en "Marcar Resuelto" (solo visible si el estado es "Pendiente" o "En Proceso").

### Paso 50: Modal de Resolución

El sistema muestra un modal emergente con:

-   Título: "Marcar como Resuelto"
-   Campo "Resolución" (área de texto obligatoria)
-   Botones: "Guardar" y "Cancelar"

### Paso 51: Ingreso de Resolución

El Recepcionista escribe la resolución del reclamo (máximo 1000 caracteres).

### Paso 52: Guardar Resolución

El Recepcionista presiona "Guardar".

### Paso 53: Actualización del Estado

El sistema actualiza el registro:

-   `estado`: "Resuelto"
-   `resolucion`: Texto ingresado
-   `fecha_resolucion`: Fecha y hora actual
-   `usuario_resolucion`: ID del usuario logueado

### Paso 54: Notificación de Resolución

El sistema muestra notificación de éxito: "Reclamo resuelto exitosamente".

### Paso 55: Actualización Visual

La fila del reclamo se actualiza mostrando:

-   Badge verde con estado "Resuelto"
-   Fecha de resolución en la tabla (si está visible)

---

## 5. Características del Listado

### 5.1 Columnas de la Tabla

1. **Nº**: Código del reclamo (cod_reclamo) - Sortable, Searchable
2. **Fecha**: Fecha del reclamo (formato dd/mm/yyyy) - Sortable
3. **Cliente**: Nombre completo del cliente - Sortable, Searchable, Limitado a 30 caracteres
4. **OS Ref.**: Número de la orden de servicio (formato "OS #123") - Sortable
5. **Vehículo**: Matrícula del vehículo (extraído de la OS)
6. **Tipo**: Descripción del tipo de reclamo - Sortable, Limitado a 20 caracteres
7. **Prioridad**: Badge de color según prioridad:
    - Rojo: Alta
    - Amarillo: Media
    - Verde: Baja
8. **Estado**: Badge de color según estado:
    - Rojo: Pendiente
    - Amarillo: En Proceso
    - Verde: Resuelto
    - Gris: Cerrado
9. **Registrado por**: Nombre del usuario que registró (columna oculta por defecto)

### 5.2 Filtros Disponibles

1. **Estado**: Combo con opciones (Pendiente, En Proceso, Resuelto, Cerrado)
    - Filtro por defecto: "Pendiente"
2. **Prioridad**: Combo con opciones (Alta, Media, Baja)
3. **Tipo de Reclamo**: Combo dinámico con tipos activos

### 5.3 Acciones de Fila

1. **Ver**: Abre vista detallada del reclamo
2. **Editar**: Permite modificar el reclamo
3. **Marcar Resuelto**: Acción rápida para resolver (solo si está Pendiente o En Proceso)

### 5.4 Acciones Masivas

1. **Eliminar**: Permite eliminar múltiples reclamos seleccionados

### 5.5 Ordenamiento

-   Por defecto: Ordenado por fecha descendente (los más recientes primero)

---

## 6. Modelo de Datos

### 6.1 Tabla: `tipo_reclamos`

```
cod_tipo_reclamo (PK, BIGINT, AUTO_INCREMENT)
descripcion (VARCHAR 100, NOT NULL)
activo (BOOLEAN, DEFAULT TRUE)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

**Registros Iniciales:**

-   Falla de Repuesto
-   Demora en el Servicio
-   Calidad de Servicio
-   Atención al Cliente
-   Precio/Facturación
-   Otros

### 6.2 Tabla: `reclamos`

```
cod_reclamo (PK, BIGINT, AUTO_INCREMENT)
cod_cliente (FK -> personas.cod_persona, NOT NULL)
cod_orden_servicio (FK -> orden_servicios.cod_orden_servicio, NOT NULL)
cod_tipo_reclamo (FK -> tipo_reclamos.cod_tipo_reclamo, NOT NULL)
fecha_reclamo (DATE, NOT NULL)
prioridad (ENUM: 'Alta', 'Media', 'Baja', DEFAULT 'Media')
descripcion (TEXT, NOT NULL)
estado (ENUM: 'Pendiente', 'En Proceso', 'Resuelto', 'Cerrado', DEFAULT 'Pendiente')
resolucion (TEXT, NULLABLE)
fecha_resolucion (DATE, NULLABLE)
usuario_resolucion (BIGINT, NULLABLE)
cod_sucursal (FK -> sucursales.cod_sucursal, NULLABLE)
usuario_alta (BIGINT, NOT NULL)
fecha_alta (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

---

## 7. Reglas de Negocio

### 7.1 Validaciones de Campos

1. **Cliente**: Obligatorio, debe existir en la tabla `personas`
2. **Orden de Servicio**: Obligatorio, debe estar en estado "Finalizado" o "Facturado"
3. **Fecha Reclamo**: Obligatorio, no puede ser fecha futura
4. **Tipo Reclamo**: Obligatorio, debe estar activo
5. **Prioridad**: Obligatorio, valores: Alta/Media/Baja
6. **Descripción**: Obligatorio, máximo 1000 caracteres
7. **Resolución**: Obligatorio cuando el estado es "Resuelto" o "Cerrado"

### 7.2 Filtrado Dinámico de Órdenes de Servicio

-   Solo se muestran las órdenes del cliente seleccionado
-   Solo se muestran órdenes con estado "Finalizado" o "Facturado"
-   Las órdenes en proceso, canceladas o pendientes NO son elegibles para reclamos

### 7.3 Estados del Reclamo

1. **Pendiente**: Estado inicial al crear el reclamo
2. **En Proceso**: Cuando se está trabajando en la resolución
3. **Resuelto**: Cuando se ha dado una solución satisfactoria
4. **Cerrado**: Cuando el reclamo se cierra definitivamente

### 7.4 Prioridades

-   **Alta**: Requiere atención inmediata, notifica al Jefe de Servicio
-   **Media**: Atención normal (valor por defecto)
-   **Baja**: Puede atenderse según disponibilidad

### 7.5 Auditoría

-   `usuario_alta`: Se registra automáticamente al crear
-   `fecha_alta`: Timestamp automático de creación
-   `usuario_resolucion`: Se registra al marcar como resuelto
-   `fecha_resolucion`: Se registra al marcar como resuelto

### 7.6 Restricciones de Eliminación

-   No se puede eliminar un cliente si tiene reclamos asociados
-   No se puede eliminar una OS si tiene reclamos asociados
-   No se puede eliminar un tipo de reclamo si está en uso

---

## 8. Notificaciones

### 8.1 Notificación de Registro Exitoso

-   **Tipo**: Success (verde)
-   **Título**: "Reclamo Registrado Exitosamente"
-   **Mensaje**: "Reclamo #[número] registrado correctamente."
-   **Duración**: 5 segundos

### 8.2 Notificación de Alta Prioridad

-   **Tipo**: Warning (amarilla)
-   **Título**: "Reclamo de Alta Prioridad Registrado"
-   **Mensaje**: "El reclamo #[número] ha sido registrado con prioridad ALTA. Se notificará al Jefe de Servicio."
-   **Persistente**: Sí (requiere cierre manual)

### 8.3 Notificación de Resolución

-   **Tipo**: Success (verde)
-   **Título**: "Reclamo resuelto exitosamente"
-   **Duración**: 5 segundos

### 8.4 Badge en Navegación

-   Muestra la cantidad de reclamos pendientes
-   Color verde: 0 pendientes
-   Color amarillo: 1-5 pendientes
-   Color rojo: Más de 5 pendientes

---

## 9. Seguridad y Permisos

### 9.1 Roles con Acceso

-   **Recepcionista**: Puede crear y ver reclamos
-   **Jefe de Servicio**: Puede ver, editar y resolver reclamos
-   **Administrador**: Acceso completo

### 9.2 Restricciones

-   Solo el usuario que creó el reclamo o un supervisor puede editarlo
-   Los reclamos cerrados no pueden ser modificados

---

## 10. Integraciones Futuras

### 10.1 Sistema de Notificaciones

-   Email al Jefe de Servicio cuando hay reclamo de alta prioridad
-   Notificación push en el sistema
-   Recordatorios automáticos para reclamos pendientes antiguos

### 10.2 Reportes y Estadísticas

-   Cantidad de reclamos por tipo
-   Tiempo promedio de resolución
-   Reclamos por cliente
-   Reclamos por vehículo/marca

### 10.3 Seguimiento de Satisfacción

-   Encuesta de satisfacción al cliente después de resolver el reclamo
-   Rating del servicio de resolución

---

## 11. Diagrama de Estados

```
[Pendiente] --> [En Proceso] --> [Resuelto] --> [Cerrado]
     |              |                |
     |              |                |
     +------[Puede volver a Pendiente]
```

---

## 12. Casos de Prueba Sugeridos

### 12.1 Caso 1: Registro Exitoso

-   **Pre-condición**: Cliente existe, OS finalizada existe
-   **Acción**: Completar todos los campos y guardar
-   **Resultado esperado**: Reclamo se crea con cod_reclamo asignado, mensaje de éxito

### 12.2 Caso 2: Validación de Campos Obligatorios

-   **Pre-condición**: Formulario abierto
-   **Acción**: Intentar guardar sin completar campos obligatorios
-   **Resultado esperado**: Mensajes de error en campos faltantes, no se guarda

### 12.3 Caso 3: Filtrado Dinámico de OS

-   **Pre-condición**: Cliente con 3 OS (1 en proceso, 1 finalizada, 1 facturada)
-   **Acción**: Seleccionar el cliente
-   **Resultado esperado**: Solo 2 OS aparecen en el combo (finalizada y facturada)

### 12.4 Caso 4: Alta Prioridad

-   **Pre-condición**: Formulario completo con prioridad "Alta"
-   **Acción**: Guardar
-   **Resultado esperado**: Reclamo guardado + notificación amarilla persistente

### 12.5 Caso 5: Marcar como Resuelto

-   **Pre-condición**: Reclamo pendiente en el listado
-   **Acción**: Clic en "Marcar Resuelto", ingresar resolución
-   **Resultado esperado**: Estado cambia a "Resuelto", badge verde, fecha y usuario de resolución registrados

---

**Fin de la Especificación**
