# Apertura y Cierre de Caja

## Descripción Básica

Este caso de uso permite a un cajero autorizado registrar la apertura de caja al inicio de su turno, registrar movimientos durante el día, y realizar el cierre de caja al finalizar su jornada. El sistema controla que solo exista una caja abierta por cajero, calcula automáticamente los saldos esperados, detecta diferencias y genera los montos a depositar.

## Actores Relacionados

-   **Cajero**: Abre y cierra la caja, registra movimientos
-   **Jefe de Ventas**: Supervisa aperturas y cierres, revisa diferencias
-   **Administrador**: Acceso completo al módulo

## Pre Condición

-   Poseer perfil de usuario con permisos para gestionar caja
-   Conexión a base de datos
-   Debe existir al menos una Caja registrada y activa
-   El usuario debe estar autenticado en el sistema
-   Opcionalmente, el usuario puede tener asignada una Sucursal (cod_sucursal)
-   El cajero NO debe tener otra caja abierta

## Tablas de Base de Datos Relacionadas

### cajas

Tabla catálogo que almacena las cajas disponibles en el sistema.

**Campos principales:**

-   `cod_caja`: Primary key (BIGINT, AUTOINCREMENT)
-   `descripcion`: Nombre de la caja (VARCHAR 100, NOT NULL) - Ej: "Caja Principal", "Caja Mostrador"
-   `cod_sucursal`: FK a sucursales (BIGINT, NULLABLE) - Sucursal donde se encuentra la caja
-   `activo`: Indica si la caja está activa (BOOLEAN, DEFAULT TRUE)
-   `usuario_alta`: Usuario que registró la caja (BIGINT)
-   `fecha_alta`: Timestamp de creación
-   `usuario_mod`: Usuario que modificó (BIGINT, NULLABLE)
-   `fecha_mod`: Timestamp de modificación (NULLABLE)
-   `created_at`: Timestamp de creación
-   `updated_at`: Timestamp de última modificación

### aperturas_caja

Tabla principal que almacena cada apertura y cierre de caja.

**Campos principales:**

**Datos de Apertura:**

-   `cod_apertura`: Primary key (BIGINT, AUTOINCREMENT)
-   `cod_caja`: FK a cajas.cod_caja (BIGINT, NOT NULL) - Caja que se abre
-   `cod_cajero`: FK a users.id (BIGINT, NOT NULL) - Cajero responsable
-   `cod_sucursal`: FK a sucursales (BIGINT, NULLABLE) - Sucursal donde se abre
-   `fecha_apertura`: Fecha de apertura (DATE, NOT NULL)
-   `hora_apertura`: Hora de apertura (TIME, NOT NULL)
-   `monto_inicial`: Efectivo inicial (DECIMAL 15,2, DEFAULT 0) - Fondo con el que se abre
-   `observaciones_apertura`: Notas de apertura (TEXT, NULLABLE)

**Datos de Cierre:**

-   `fecha_cierre`: Fecha de cierre (DATE, NULLABLE)
-   `hora_cierre`: Hora de cierre (TIME, NULLABLE)
-   `efectivo_real`: Efectivo físico contado (DECIMAL 15,2, NULLABLE)
-   `saldo_esperado`: Saldo calculado por el sistema (DECIMAL 15,2, NULLABLE)
-   `diferencia`: Diferencia entre real y esperado (DECIMAL 15,2, NULLABLE) - Puede ser positiva (sobrante) o negativa (faltante)
-   `monto_depositar`: Monto que excede el fondo inicial (DECIMAL 15,2, NULLABLE)
-   `observaciones_cierre`: Notas de cierre (TEXT, NULLABLE)

**Control:**

-   `estado`: Estado actual (ENUM: 'Abierta', 'Cerrada', DEFAULT 'Abierta')
-   `usuario_alta`: Usuario que abrió (BIGINT)
-   `fecha_alta`: Timestamp de apertura
-   `usuario_mod`: Usuario que cerró (BIGINT, NULLABLE)
-   `fecha_mod`: Timestamp de cierre (NULLABLE)
-   `created_at`: Timestamp de creación
-   `updated_at`: Timestamp de última modificación

**Constraints:**

-   FK `cod_caja` → `cajas(cod_caja)` ON DELETE RESTRICT
-   FK `cod_cajero` → `users(id)` ON DELETE RESTRICT

### movimientos_caja

Tabla que registra todos los movimientos de entrada y salida de efectivo durante el día.

**Campos principales:**

-   `cod_movimiento`: Primary key (BIGINT, AUTOINCREMENT)
-   `cod_apertura`: FK a aperturas_caja.cod_apertura (BIGINT, NOT NULL)
-   `tipo_movimiento`: Tipo de movimiento (ENUM: 'Ingreso', 'Egreso', NOT NULL)
-   `concepto`: Concepto del movimiento (VARCHAR 100) - Ej: "Venta", "Gasto", "Retiro"
-   `tipo_documento`: Tipo de documento origen (VARCHAR 50, NULLABLE) - Ej: "Factura", "Recibo"
-   `documento_id`: ID del documento origen (BIGINT, NULLABLE)
-   `monto`: Monto del movimiento (DECIMAL 15,2, NOT NULL)
-   `descripcion`: Descripción detallada (TEXT, NULLABLE)
-   `fecha_movimiento`: Fecha y hora del movimiento (DATETIME, NOT NULL)
-   `usuario_alta`: Usuario que registró (BIGINT)
-   `fecha_alta`: Timestamp de registro
-   `created_at`: Timestamp de creación
-   `updated_at`: Timestamp de última modificación

**Constraints:**

-   FK `cod_apertura` → `aperturas_caja(cod_apertura)` ON DELETE CASCADE

---

## Flujo de Eventos

### Flujo Básico

**Listado de Aperturas de Caja**

1. El usuario selecciona en el menú el grupo "Ventas"
2. El usuario hace clic en el ítem "Apertura de Caja"
3. El sistema abre la interfaz Apertura de Caja, que muestra las aperturas ya registradas
4. El sistema llama conexión
5. El sistema consulta los datos de la tabla `aperturas_caja` y sus tablas relacionadas:

```sql
SELECT
    ac.cod_apertura,
    ac.fecha_apertura,
    ac.fecha_cierre,
    ac.monto_inicial,
    ac.diferencia,
    ac.estado,
    c.descripcion AS caja_nombre,
    u.name AS cajero_nombre
FROM aperturas_caja ac
INNER JOIN cajas c ON ac.cod_caja = c.cod_caja
INNER JOIN users u ON ac.cod_cajero = u.id
ORDER BY ac.fecha_apertura DESC, ac.created_at DESC;
```

6. El sistema agrega los datos a la grilla con las columnas:
    - Nº (cod_apertura)
    - Caja (descripción de la caja)
    - Cajero (nombre del usuario)
    - Fecha Apertura (formato dd/mm/yyyy)
    - Monto Inicial (formato moneda Gs.)
    - Fecha Cierre (formato dd/mm/yyyy o "N/A")
    - Estado (badge: Verde=Cerrada, Amarillo=Abierta)
    - Diferencia (formato moneda, color: Verde=0, Amarillo=positiva, Rojo=negativa)
7. El sistema calcula el badge del menú mostrando la cantidad de cajas abiertas actualmente
8. El sistema muestra el botón "Nueva Apertura" en la parte superior

### Crear Apertura de Caja

9. El Cajero está en la pantalla Listado de Aperturas de Caja
10. El Cajero presiona el botón "Nueva Apertura"
11. El sistema redirecciona a la interfaz Crear Apertura de Caja
12. El sistema llama conexión
13. El sistema verifica si el cajero ya tiene una caja abierta:

```sql
SELECT COUNT(*)
FROM aperturas_caja
WHERE cod_cajero = {user_id}
  AND estado = 'Abierta';
```

14. SI el cajero ya tiene una caja abierta:
    -   El sistema muestra notificación de error (roja, persistente)
    -   Título: "Error: Ya tiene una caja abierta"
    -   Mensaje: "Debe cerrar su caja actual antes de abrir una nueva."
    -   El sistema previene la creación
    -   **[FIN DE FLUJO]**
15. El sistema recupera datos del sistema automáticamente:

    -   cod_cajero = Auth::id()
    -   cod_sucursal = Auth::user()->cod_sucursal ?? null
    -   fecha_apertura = now()->toDateString()
    -   hora_apertura = now()->toTimeString()
    -   estado = 'Abierta'

16. El sistema muestra el formulario con la **Sección 1: Información de Apertura**:
    -   Campo "Caja" (Select obligatorio, habilitado)
    -   Placeholder "Cajero" (Solo lectura, muestra nombre del usuario actual)
    -   Campo "Fecha de Apertura" (DatePicker precargado con hoy, deshabilitado)
    -   Campo "Hora de Apertura" (TimePicker precargado con hora actual, deshabilitado)
    -   Campo "Monto Inicial (Efectivo)" (TextInput numérico, obligatorio, default 0)
    -   Campo "Observaciones de Apertura" (Textarea opcional, máximo 1000 caracteres)

**Selección de la Caja**

17. El Cajero hace clic en el campo "Caja"
18. El sistema llama conexión
19. El sistema consulta las cajas activas:

```sql
SELECT cod_caja, descripcion
FROM cajas
WHERE activo = true
ORDER BY descripcion;
```

20. El sistema carga el combo con las cajas disponibles
21. El Cajero selecciona una Caja
22. El sistema valida que la caja seleccionada NO esté abierta:

```sql
SELECT COUNT(*)
FROM aperturas_caja
WHERE cod_caja = {cod_caja_seleccionada}
  AND estado = 'Abierta';
```

23. SI la caja ya está abierta por otro cajero:
    -   El sistema muestra error: "Esta caja ya está abierta. Debe cerrarse primero."
    -   El sistema limpia la selección
    -   **[Volver al paso 17]**
24. El sistema guarda el cod_caja seleccionado

**Ingresar Monto Inicial**

25. El Cajero hace clic en el campo "Monto Inicial (Efectivo)"
26. El Cajero ingresa el monto en efectivo con el que abre la caja
27. El sistema valida que el monto sea numérico y mayor o igual a 0
28. El Cajero puede escribir observaciones opcionales (máximo 1000 caracteres)

**Guardar Apertura**

29. El Cajero revisa los datos ingresados
30. El Cajero presiona el botón "Crear" en la parte inferior
31. El sistema valida que todos los campos obligatorios estén completos:
    -   Caja seleccionada
    -   Fecha de apertura
    -   Hora de apertura
    -   Monto inicial ingresado
32. SI falta algún campo obligatorio:
    -   El sistema muestra mensaje de error en rojo debajo del campo
    -   El sistema previene el guardado
    -   **[Volver al paso 29]**
33. El sistema llama conexión
34. El sistema prepara los datos para inserción:

```php
$data = [
    'cod_caja' => $cod_caja,
    'cod_cajero' => Auth::id(),
    'cod_sucursal' => Auth::user()->cod_sucursal ?? null,
    'fecha_apertura' => now()->toDateString(),
    'hora_apertura' => now()->toTimeString(),
    'monto_inicial' => $monto_inicial,
    'observaciones_apertura' => $observaciones,
    'estado' => 'Abierta',
    'usuario_alta' => Auth::id(),
    'fecha_alta' => now(),
    'created_at' => now(),
    'updated_at' => now()
];
```

35. El sistema ejecuta INSERT en la tabla `aperturas_caja`:

```sql
INSERT INTO aperturas_caja (
    cod_caja,
    cod_cajero,
    cod_sucursal,
    fecha_apertura,
    hora_apertura,
    monto_inicial,
    observaciones_apertura,
    estado,
    usuario_alta,
    fecha_alta,
    created_at,
    updated_at
) VALUES (
    {cod_caja},
    {cod_cajero},
    {cod_sucursal},
    '{fecha_apertura}',
    '{hora_apertura}',
    {monto_inicial},
    '{observaciones}',
    'Abierta',
    {usuario_alta},
    NOW(),
    NOW(),
    NOW()
) RETURNING cod_apertura;
```

36. La base de datos genera automáticamente el cod_apertura (AUTOINCREMENT)
37. El sistema recibe el cod_apertura asignado
38. El sistema muestra notificación de éxito (verde, 5 segundos):
    -   Título: "Caja Abierta Exitosamente"
    -   Mensaje: "La caja ha sido abierta y está lista para operar."
39. El sistema redirige automáticamente al "Listado de Aperturas de Caja"
40. El sistema actualiza la grilla mostrando la nueva apertura en la primera fila
41. El sistema actualiza el badge del menú incrementando el contador de cajas abiertas

### Ver Detalles de una Apertura

42. Desde el listado, el Cajero hace clic en el botón "Ver" (ícono de ojo) de una apertura
43. El sistema llama conexión
44. El sistema consulta los datos completos de la apertura:

```sql
SELECT
    ac.*,
    c.descripcion AS caja_nombre,
    u_cajero.name AS cajero_nombre,
    u_alta.name AS usuario_alta_nombre
FROM aperturas_caja ac
INNER JOIN cajas c ON ac.cod_caja = c.cod_caja
INNER JOIN users u_cajero ON ac.cod_cajero = u_cajero.id
LEFT JOIN users u_alta ON ac.usuario_alta = u_alta.id
WHERE ac.cod_apertura = {cod_apertura};
```

45. El sistema abre la vista detallada (modo solo lectura)
46. El sistema muestra todas las secciones según el estado:

**Si está Abierta:**

-   Sección 1: Información de Apertura (solo lectura)
-   Sección 2: Resumen de Movimientos
-   Sección 3: Auditoría

**Si está Cerrada:**

-   Sección 1: Información de Apertura (solo lectura)
-   Sección 2: Resumen de Movimientos
-   Sección 3: Datos de Cierre
-   Sección 4: Auditoría

47. El sistema muestra botón "Cerrar Caja" solo si el estado es "Abierta"
48. El Cajero puede hacer clic en "Atrás" para volver al listado

### Cerrar Caja (Editar Apertura Abierta)

49. Desde el listado, el Cajero hace clic en el botón "Cerrar" (solo visible si estado = Abierta)
50. El sistema redirecciona a la interfaz de edición
51. El sistema llama conexión
52. El sistema consulta los datos de la apertura (misma query del paso 44)
53. El sistema calcula el resumen de movimientos:

```sql
SELECT
    SUM(CASE WHEN tipo_movimiento = 'Ingreso' THEN monto ELSE 0 END) AS total_ingresos,
    SUM(CASE WHEN tipo_movimiento = 'Egreso' THEN monto ELSE 0 END) AS total_egresos
FROM movimientos_caja
WHERE cod_apertura = {cod_apertura};
```

```php
$totalIngresos = MovimientoCaja::where('cod_apertura', $cod_apertura)
    ->where('tipo_movimiento', 'Ingreso')
    ->sum('monto');

$totalEgresos = MovimientoCaja::where('cod_apertura', $cod_apertura)
    ->where('tipo_movimiento', 'Egreso')
    ->sum('monto');

$saldoEsperado = $monto_inicial + $totalIngresos - $totalEgresos;
```

54. El sistema muestra el formulario con las secciones:

**Sección 1: Información de Apertura** (toda en solo lectura)

-   Caja
-   Cajero
-   Fecha/Hora Apertura
-   Monto Inicial
-   Observaciones Apertura

**Sección 2: Resumen de Movimientos del Día** (auto-calculado)

-   Total Ingresos: XXX,XXX Gs.
-   Total Egresos: XXX,XXX Gs.
-   Saldo Esperado (Sistema): XXX,XXX Gs. (Monto Inicial + Ingresos - Egresos)

**Sección 3: Cierre de Caja** (campos editables)

-   Efectivo Real Contado (input obligatorio)
-   Diferencia (calculado automáticamente, solo lectura)
-   Monto a Depositar (calculado automáticamente, solo lectura)
-   Fecha de Cierre (auto: hoy, deshabilitado)
-   Hora de Cierre (auto: hora actual, deshabilitado)
-   Observaciones de Cierre (textarea opcional)

**Ingresar Efectivo Real**

55. El Cajero hace clic en el campo "Efectivo Real Contado"
56. El Cajero ingresa el efectivo físicamente contado en la caja
57. El sistema calcula automáticamente (en tiempo real con live):

```php
$diferencia = $efectivo_real - $saldo_esperado;
$monto_depositar = max(0, $efectivo_real - $monto_inicial);
```

58. El sistema actualiza el campo "Diferencia" mostrando:
    -   Si diferencia = 0: "0 Gs. (OK)" con badge verde
    -   Si diferencia > 0: "+ X Gs. (Sobrante)" con badge amarillo
    -   Si diferencia < 0: "- X Gs. (Faltante)" con badge rojo
59. El sistema actualiza el campo "Monto a Depositar" (efectivo_real - monto_inicial)
60. El Cajero puede escribir observaciones sobre el cierre (máximo 1000 caracteres)

**Guardar Cierre**

61. El Cajero revisa los datos calculados
62. El Cajero presiona el botón "Guardar" en la parte inferior
63. El sistema valida que el campo "Efectivo Real Contado" esté ingresado
64. SI está vacío:
    -   El sistema muestra error: "El efectivo real es obligatorio para cerrar la caja"
    -   **[Volver al paso 55]**
65. El sistema llama conexión
66. El sistema prepara los datos de cierre:

```php
$data = [
    'estado' => 'Cerrada',
    'fecha_cierre' => now()->toDateString(),
    'hora_cierre' => now()->toTimeString(),
    'efectivo_real' => $efectivo_real,
    'saldo_esperado' => $saldo_esperado,
    'diferencia' => $efectivo_real - $saldo_esperado,
    'monto_depositar' => max(0, $efectivo_real - $monto_inicial),
    'observaciones_cierre' => $observaciones,
    'usuario_mod' => Auth::id(),
    'fecha_mod' => now(),
    'updated_at' => now()
];
```

67. El sistema ejecuta UPDATE en la tabla `aperturas_caja`:

```sql
UPDATE aperturas_caja SET
    estado = 'Cerrada',
    fecha_cierre = CURRENT_DATE,
    hora_cierre = CURRENT_TIME,
    efectivo_real = {efectivo_real},
    saldo_esperado = {saldo_esperado},
    diferencia = {diferencia},
    monto_depositar = {monto_depositar},
    observaciones_cierre = '{observaciones}',
    usuario_mod = {user_id},
    fecha_mod = NOW(),
    updated_at = NOW()
WHERE cod_apertura = {cod_apertura};
```

68. El sistema verifica si hay diferencia
69. SI diferencia != 0:
    -   El sistema muestra notificación de advertencia (amarilla, persistente)
    -   Título: "Diferencia Detectada: [Sobrante/Faltante]"
    -   Mensaje: "Se detectó una diferencia de X Gs. Verifique las observaciones."
70. SI diferencia = 0:
    -   El sistema muestra notificación de éxito (verde, 5 segundos)
    -   Título: "Caja Cerrada Exitosamente"
    -   Mensaje: "La caja ha sido cerrada correctamente. Cuadre perfecto."
71. El sistema redirige automáticamente al "Listado de Aperturas de Caja"
72. El sistema actualiza la grilla mostrando la apertura con estado "Cerrada"
73. El sistema actualiza el badge del menú decrementando el contador de cajas abiertas
74. **[FIN DE FLUJO]**

---

## Reglas de Negocio

### Validaciones

1. **Un cajero solo puede tener UNA caja abierta a la vez**
2. **Una caja solo puede estar abierta por UN cajero a la vez**
3. **El monto inicial debe ser mayor o igual a 0**
4. **No se puede eliminar una apertura si tiene movimientos asociados**
5. **El efectivo real es obligatorio para cerrar la caja**
6. **Una vez cerrada, la caja NO puede reabrirse (estado inmutable)**

### Cálculos Automáticos

1. **Saldo Esperado** = Monto Inicial + Total Ingresos - Total Egresos
2. **Diferencia** = Efectivo Real - Saldo Esperado
3. **Monto a Depositar** = MAX(0, Efectivo Real - Monto Inicial)

### Estados

-   **Abierta**: La caja está operativa, se pueden registrar movimientos
-   **Cerrada**: La caja está cerrada, no se pueden agregar movimientos

### Diferencias

-   **Cuadre Perfecto** (diferencia = 0): Badge verde, notificación de éxito
-   **Sobrante** (diferencia > 0): Badge amarillo, notificación de advertencia
-   **Faltante** (diferencia < 0): Badge rojo, notificación de advertencia

---

## Notificaciones

### Apertura Exitosa

-   **Tipo**: Success (verde)
-   **Título**: "Caja Abierta Exitosamente"
-   **Mensaje**: "La caja ha sido abierta y está lista para operar."
-   **Duración**: 5 segundos

### Error: Cajero con Caja Abierta

-   **Tipo**: Danger (roja)
-   **Título**: "Error: Ya tiene una caja abierta"
-   **Mensaje**: "Debe cerrar su caja actual antes de abrir una nueva."
-   **Persistente**: Sí

### Error: Caja Ya Abierta

-   **Tipo**: Error (roja)
-   **Mensaje**: "Esta caja ya está abierta. Debe cerrarse primero."

### Cierre con Cuadre Perfecto

-   **Tipo**: Success (verde)
-   **Título**: "Caja Cerrada Exitosamente"
-   **Mensaje**: "La caja ha sido cerrada correctamente. Cuadre perfecto."
-   **Duración**: 5 segundos

### Cierre con Diferencia

-   **Tipo**: Warning (amarilla)
-   **Título**: "Diferencia Detectada: [Sobrante/Faltante]"
-   **Mensaje**: "Se detectó una diferencia de X Gs. Verifique las observaciones."
-   **Persistente**: Sí

### Badge en Navegación

-   Muestra la cantidad de cajas abiertas actualmente
-   Color: Amarillo (warning)
-   Solo se muestra si hay cajas abiertas

---

## Seguridad y Permisos

### Roles con Acceso

-   **Cajero**: Puede abrir y cerrar su propia caja
-   **Jefe de Ventas**: Puede ver todas las aperturas y cierres
-   **Administrador**: Acceso completo

### Restricciones

-   Un cajero solo puede cerrar su propia caja
-   No se puede modificar una caja cerrada
-   No se puede eliminar una apertura con movimientos

---

## Integraciones Futuras

### Arqueo de Caja

-   Verificación intermedia durante el día
-   Conteo físico vs. sistema
-   Alertas de discrepancias

### Recaudaciones y Depósitos

-   Registro de depósitos bancarios
-   Seguimiento de efectivo depositado
-   Conciliación bancaria

### Reportes y Estadísticas

-   Ingresos/Egresos por cajero
-   Diferencias históricas
-   Rendimiento por caja
-   Alertas de diferencias recurrentes

### Dashboard

-   Vista en tiempo real de cajas abiertas
-   Movimientos del día
-   Alertas de seguridad

---

**Versión:** 1.0  
**Fecha:** 09 de noviembre de 2025  
**Módulo:** Ventas - Apertura y Cierre de Caja  
**Sistema:** Alto Pistón - Sistema de Gestión de Taller

---

**Fin de la Especificación**
