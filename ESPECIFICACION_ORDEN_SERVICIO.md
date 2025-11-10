# Orden de Servicio

## Descripción Básica

Este caso de uso permite a un usuario autorizado generar órdenes de servicio a partir de presupuestos aprobados, gestionando automáticamente la reserva de stock multi-sucursal y aplicando promociones vigentes.

## Actores Relacionados

-   Jefe de Servicios (JDS)
-   Mecánico
-   Recepcionista

## Pre Condición

-   Poseer perfil de usuario con permisos para crear órdenes de servicio
-   Conexión a base de datos
-   Debe existir un Presupuesto con estado "Aprobado"
-   Debe existir un Diagnóstico vinculado al presupuesto
-   Debe existir una Recepción de Vehículo vinculada
-   Deben existir Artículos registrados con precios
-   Debe existir un módulo de Promociones con fechas de vigencia
-   El usuario debe tener asignada una Sucursal (cod_sucursal)
-   Debe existir stock en la tabla existe_stock por sucursal

## Tablas de Base de Datos Relacionadas

### orden_servicios

Tabla principal que almacena la información de cabecera de cada orden de servicio.

**Campos principales:**

-   `id`: Primary key
-   `presupuesto_venta_id`: FK a presupuesto_ventas (requerido)
-   `diagnostico_id`: FK a diagnostico_mecanico (inherited from presupuesto)
-   `recepcion_vehiculo_id`: FK a recepcion_vehiculos (inherited from presupuesto)
-   `cliente_id`: FK a personas (inherited from presupuesto)
-   `cod_sucursal`: FK a sucursal (asignado automáticamente del usuario)
-   `fecha_inicio`: Fecha de inicio del trabajo
-   `fecha_estimada_finalizacion`: Fecha estimada de finalización
-   `fecha_finalizacion_real`: Fecha real de finalización
-   `estado_trabajo`: Estado actual (Pendiente, En Proceso, Completado, etc.)
-   `mecanico_asignado_id`: FK a empleados
-   `observaciones_tecnicas`: Notas técnicas del trabajo realizado
-   `observaciones_internas`: Notas internas no visibles para el cliente
-   `total`: Monto total de la orden
-   `usuario_alta`, `fec_alta`, `usuario_mod`, `fec_mod`: Auditoría

### orden_servicio_detalles

Tabla de detalles que almacena cada artículo/servicio de la orden.

**Campos principales:**

-   `id`: Primary key
-   `orden_servicio_id`: FK a orden_servicios
-   `cod_articulo`: FK a articulos (requerido)
-   `descripcion`: Descripción del artículo
-   `cantidad`: Cantidad solicitada
-   `cantidad_utilizada`: Cantidad realmente utilizada (para control)
-   `precio_unitario`: Precio unitario del artículo (requerido)
-   `porcentaje_descuento`: % de descuento aplicado
-   `monto_descuento`: Monto en Gs del descuento
-   `subtotal`: Subtotal antes de impuestos
-   `porcentaje_impuesto`: % de IVA (default 10%)
-   `monto_impuesto`: Monto en Gs del IVA
-   `total`: Total del detalle (subtotal + IVA)
-   `stock_reservado`: Boolean - indica si el stock está reservado
-   `presupuesto_venta_detalle_id`: FK opcional a presupuesto_venta_detalles (para trazabilidad)
-   `usuario_alta`, `fec_alta`, `usuario_mod`, `fec_mod`: Auditoría

### existe_stock

Tabla de control de inventario multi-sucursal.

**Campos principales:**

-   `id`: Primary key
-   `cod_articulo`: FK a articulos
-   `cod_sucursal`: FK a sucursal
-   `stock_actual`: Cantidad física en sucursal
-   `stock_reservado`: Cantidad reservada para OS pendientes
-   `stock_minimo`: Nivel mínimo de alerta
-   Constraint: `unique(cod_articulo, cod_sucursal)`

---

## Flujo de Eventos

### Flujo Básico

**Listado de Órdenes de Servicio**

1. El usuario selecciona en el menú el ítem Orden de Servicio
2. El sistema abre la interfaz Orden de Servicio, que muestra las órdenes ya generadas
3. El sistema llama conexión
4. El sistema consulta los datos de la tabla orden_servicios y sus tablas relacionadas (Cliente, Vehículo, Presupuesto, Mecánico)
5. El sistema agrega los datos a la grilla (N.° OS, N.° Presup., Cliente, Vehículo, Mecánico, Fecha Inicio, Estado, Total)

### Crear Orden de Servicio

1. El Jefe de Servicio (JDS) está en la pantalla Listado de Órdenes de Servicio
2. El JDS presiona el botón "Crear"
3. El sistema redirecciona a la interfaz Crear Orden de Servicio
4. El sistema llama conexión
5. El sistema recupera datos del sistema (sucursal del usuario, usuario_alta, fec_alta)
6. El sistema llama conexión
7. El sistema consulta la tabla presupuesto_ventas filtrando por estado "Aprobado"
8. El sistema carga el combo "Presupuesto de Venta" con los presupuestos aprobados
9. El JDS selecciona un Presupuesto de Venta del combo
10. El sistema llama conexión
11. 11. El sistema consulta los datos del Presupuesto seleccionado (incluyendo cliente, diagnostico, recepcion_vehiculo)
12. El sistema carga y bloquea automáticamente los campos:
    - Cliente (del presupuesto)
    - Diagnóstico (del presupuesto)
    - Recepción de Vehículo (del presupuesto)
    - Total (del presupuesto)
    - Sucursal (del usuario autenticado)
13. El sistema llama conexión
14. El sistema consulta la tabla presupuesto_venta_detalle para obtener todos los artículos del presupuesto
15. El sistema carga automáticamente el "Detalle de Artículos" con todos los items del presupuesto
16. Para cada artículo del presupuesto, el sistema bloquea los campos:
    - Artículo
    - Cantidad
    - Precio Unitario
    - % Descuento
    - Subtotal
    - Total
17. El sistema marca cada item como "Del presupuesto" (presupuesto_venta_detalle_id asignado)
18. El sistema carga automáticamente el estado "Pendiente"
19. El sistema carga automáticamente la fecha_inicio con la fecha actual
20. El sistema llama conexión
21. El sistema consulta la tabla empleados para obtener mecánicos disponibles
22. El sistema carga el combo "Mecánico Asignado"
23. El JDS selecciona un Mecánico Asignado
24. El JDS selecciona la Fecha Estimada de Finalización
25. El JDS escribe Observaciones Técnicas (opcional)
26. El JDS escribe Observaciones Internas (opcional)

**Agregar Artículos Adicionales (Opcional)**

27. El JDS presiona el botón "Agregar artículo"
28. El sistema agrega una nueva línea en el "Detalle de Artículos"
29. El sistema llama conexión
30. El sistema consulta datos de la tabla articulos
31. El sistema carga el combo Artículo (sin bloquear)
32. El JDS selecciona un Artículo adicional
33. El sistema llama conexión
34. El sistema consulta la tabla articulos para obtener el precio
35. El sistema consulta la tabla existe_stock filtrando por cod_articulo y cod_sucursal
36. El sistema calcula stock_disponible = stock_actual - stock_reservado
37. El sistema muestra notificación informativa: "Hay X unidades disponibles de [Artículo]"
38. SI NO existe registro de stock:
    -   El sistema muestra notificación de advertencia: "No existe registro de stock para [Artículo] en esta sucursal"
39. El sistema consulta la tabla promociones, promocion_detalle para descuentos vigentes
40. SI el artículo tiene promoción vigente:
    -   El sistema carga el "Precio Unit." del artículo
    -   El sistema carga el "% Desc." de la promoción
    -   El sistema muestra notificación "¡Promoción aplicada! Descuento del X% aplicado"
    -   El campo "% Desc." queda editable
41. SI el artículo NO tiene promoción:
    -   El sistema carga el "Precio Unit." del artículo
    -   El campo "% Desc." queda en 0 y editable
42. El JDS ingresa la Cantidad
43. El sistema valida en tiempo real la cantidad contra el stock disponible:
    -   SI cantidad > stock_disponible:
        -   El sistema muestra notificación persistente de advertencia: "Stock insuficiente. Solo hay X unidades disponibles de [Artículo] en esta sucursal. Solicitado: Y"
        -   El sistema permite continuar (la orden se creará con advertencia)
    -   SI cantidad <= stock_disponible:
        -   El sistema muestra notificación de éxito: "Stock disponible. Hay X unidades disponibles"
44. El sistema calcula automáticamente:
    -   monto_descuento = (cantidad × precio_unitario) × (% descuento / 100)
    -   subtotal = (cantidad × precio_unitario) - monto_descuento
    -   monto_impuesto = subtotal × 10%
    -   total = subtotal + monto_impuesto
45. El sistema recalcula el Total general de la orden
46. El sistema marca el item como "Artículo adicional" (presupuesto_venta_detalle_id NULL)
47. El JDS puede repetir los pasos 27-46 para agregar más artículos

**Guardar Orden de Servicio**

48. El JDS presiona el botón "Crear"
49. El sistema valida que todos los campos obligatorios (\*) estén completos:
    -   presupuesto_venta_id (requerido)
    -   Mínimo 1 artículo en el detalle
    -   cod_articulo en cada detalle (requerido)
    -   precio_unitario en cada detalle (requerido)
50. El sistema llama conexión
51. El sistema inserta los datos de la cabecera en la tabla orden_servicios:
    -   presupuesto_venta_id
    -   diagnostico_id (del presupuesto)
    -   recepcion_vehiculo_id (del presupuesto)
    -   cliente_id (del presupuesto)
    -   cod_sucursal (del usuario)
    -   fecha_inicio
    -   fecha_estimada_finalizacion
    -   estado_trabajo (Pendiente)
    -   mecanico_asignado_id
    -   observaciones_tecnicas
    -   observaciones_internas
    -   total
    -   usuario_alta, fec_alta
52. El sistema inserta cada ítem del "Detalle de artículos" en la tabla orden_servicio_detalles
53. El sistema llama conexión
54. El sistema inserta cada ítem del "Detalle de artículos" en la tabla orden_servicio_detalles
55. El sistema llama conexión

**Reserva Automática de Stock (Observer)**

54. Para cada detalle insertado, el sistema ejecuta automáticamente (OrdenServicioDetalleObserver):
    -   Busca el artículo en la tabla existe_stock filtrando por cod_articulo y cod_sucursal
    -   Calcula stock_disponible = stock_actual - stock_reservado
    -   SI stock_disponible >= cantidad solicitada:
        -   Incrementa stock_reservado en la cantidad solicitada
        -   Marca detalle.stock_reservado = true
        -   Registra usuario_mod, fec_mod
        -   Agrega mensaje: "✅ [Artículo]: X unidades reservadas"
    -   SI stock_disponible < cantidad solicitada:
        -   NO incrementa stock_reservado
        -   Marca detalle.stock_reservado = false
        -   Agrega mensaje: "❌ [Artículo]: Solicitado X, Disponible Y"
55. El sistema recopila todos los mensajes de reserva de stock

**Resultado Final**

56. SI todos los artículos fueron reservados exitosamente:
    -   El sistema emite un mensaje de éxito: "Orden de servicio creada. El stock ha sido reservado correctamente."
57. SI algunos artículos NO pudieron ser reservados:
    -   El sistema emite un mensaje de advertencia: "Orden de servicio creada con advertencia. No se pudo reservar todo el stock:"
    -   El sistema lista cada artículo con su estado de reserva
58. El sistema redirecciona al Listado de Órdenes de Servicio

---

### Finalizar Orden de Servicio

1. El Jefe de Servicio (JDS) está en el listado Orden de Servicio
2. El JDS ingresa un filtro a buscar
3. El sistema filtra los datos de la lista
4. El JDS selecciona una orden con estado "Pendiente", "En Proceso" o "Pausado"
5. El JDS presiona el botón "Finalizar Trabajo" en el menú de acciones
6. El sistema emite un mensaje de confirmación "¿Confirmar finalización del trabajo?"
7. El JDS confirma el mensaje
8. El sistema llama conexión
9. El sistema actualiza el estado de la orden_servicios a "Finalizado"
10. El sistema actualiza fecha_finalizacion_real con la fecha y hora actual
11. El sistema emite un mensaje "Orden de servicio finalizada. El trabajo ha sido marcado como finalizado."
12. El sistema llama conexión
13. El sistema consulta los datos actualizados de la tabla orden_servicios
14. El sistema actualiza la grilla

**Nota:** El stock permanece reservado hasta que se facture o se cancele la orden

---

### Cancelar Orden de Servicio

1. El Jefe de Servicio (JDS) está en el listado Orden de Servicio
2. El JDS ingresa un filtro a buscar
3. El sistema filtra los datos de la lista
4. El JDS selecciona una orden que NO esté en estado "Cancelado" ni "Facturado"
5. El JDS presiona el botón "Cancelar OS" en el menú de acciones
6. El sistema emite un mensaje de confirmación "¿Está seguro de cancelar esta orden de servicio?"
7. El JDS confirma el mensaje
8. El sistema llama conexión

**Liberación Automática de Stock (Método liberarStock)**

9. Para cada detalle de la orden con stock_reservado = true:
    - El sistema busca el artículo en la tabla existe_stock (cod_articulo, cod_sucursal)
    - Decrementa stock_reservado en la cantidad reservada
    - Marca detalle.stock_reservado = false
    - Registra usuario_mod, fec_mod
10. El sistema actualiza el estado de la orden_servicios a "Cancelado"
11. El sistema emite un mensaje "Orden de servicio cancelada. El stock reservado ha sido liberado."
12. El sistema llama conexión
13. El sistema consulta los datos actualizados de la tabla orden_servicios
14. El sistema actualiza la grilla

---

### Ver Orden de Servicio

1. El Jefe de Servicio (JDS) está en el listado Orden de Servicio
2. El JDS ingresa un filtro a buscar
3. El sistema filtra los datos de la lista
4. El JDS selecciona una orden y presiona el botón "Ver"
5. El sistema abre la vista detallada de la Orden de Servicio
6. El sistema llama conexión
7. El sistema consulta los datos de la tabla orden_servicios
8. El sistema consulta los datos de la tabla orden_servicio_detalles para ver los items
9. El sistema consulta las tablas relacionadas:
    - personas (Cliente)
    - presupuesto_ventas
    - diagnostico
    - recepcion_vehiculos, vehiculos (Vehículo)
    - empleados, personas (Mecánico)
    - articulos (Artículos del detalle)
    - sucursal
10. El sistema agrega todos los datos en los campos correspondientes, en modo solo lectura
11. El sistema muestra los badges de estado según el estado_trabajo
12. El sistema muestra indicadores visuales:
    - "🔒 Del presupuesto" para artículos originales del presupuesto
    - "🆕 Artículo adicional" para artículos agregados manualmente
    - "✓ Stock reservado" para items con stock reservado
    - "✗ Sin reserva" para items sin stock reservado
13. El JDS presiona el botón "Volver"
14. El sistema redirecciona al Listado de Órdenes de Servicio

---

### Imprimir PDF de Orden de Servicio

1. El Jefe de Servicio (JDS) está en el listado Orden de Servicio o en la vista detallada
2. El JDS selecciona una orden
3. El JDS presiona el botón "Imprimir OS" o "Ver PDF" en el menú de acciones
4. El sistema llama conexión
5. El sistema consulta los datos completos de la orden_servicios con todas sus relaciones:
    - cliente
    - presupuestoVenta
    - diagnostico
    - recepcionVehiculo.vehiculo.marca
    - recepcionVehiculo.vehiculo.modelo
    - sucursal
    - mecanicoAsignado.persona
    - detalles.articulo
6. El sistema genera el PDF usando la vista "pdf.orden-servicio.blade.php"
7. El PDF incluye:
    - Cabecera corporativa (nombre empresa, dirección, teléfono)
    - Número de OS y fecha de generación
    - Información del cliente (nombre, documento, teléfono)
    - Información del vehículo (matrícula, marca, modelo, año, color, kilometraje)
    - Diagnóstico mecánico
    - Datos del servicio (mecánico, fechas, estado)
    - Tabla detallada de artículos con:
        - Código y descripción
        - Cantidad
        - Precio unitario
        - % Descuento
        - Subtotal
        - IVA
        - Total por línea
        - Indicador si es del presupuesto o adicional
        - Badge de stock reservado
    - Totales generales (Subtotal, Descuentos, IVA, TOTAL)
    - Observaciones técnicas
    - Observaciones internas
    - Sección de firmas (cliente y mecánico)
    - Footer con fecha de generación y usuario
8. SI el JDS presionó "Imprimir OS":
    - El sistema descarga el archivo PDF automáticamente
    - Nombre del archivo: "Orden*Servicio*[ID]\_[FechaHora].pdf"
9. SI el JDS presionó "Ver PDF":
    - El sistema abre el PDF en una nueva pestaña del navegador
10. El sistema retorna a la pantalla anterior

---

## Estados de la Orden de Servicio

12. **Pendiente** (Estado inicial)

    -   OS creada, stock reservado
    -   Esperando asignación o inicio de trabajo

13. **En Proceso**

    -   Trabajo en ejecución
    -   Stock sigue reservado

14. **Completado**

    -   Trabajo finalizado
    -   Pendiente de facturación
    -   Stock aún reservado

15. **Facturado**

    -   Stock descontado definitivamente
    -   Stock_reservado liberado
    -   Stock_actual decrementado

16. **Cancelado**
    -   Stock reservado liberado automáticamente
    -   No afecta stock_actual

---

## 4. Gestión de Stock Multi-Sucursal

### 4.1 Modelo de Tres Fases

```
┌──────────────────┐
│  STOCK_ACTUAL    │  ← Stock físico en sucursal
│     (100 u)      │
└──────────────────┘
        ↓
┌──────────────────┐
│ STOCK_RESERVADO  │  ← Reservado para OS
│     (30 u)       │
└──────────────────┘
        ↓
┌──────────────────┐
│STOCK_DISPONIBLE  │  ← Disponible = Actual - Reservado
│     (70 u)       │     (calculado)
└──────────────────┘
```

### 4.2 Operaciones de Stock

#### `reservarStock($cantidad, $codSucursal)`

**Cuándo:** Al crear OS o agregar detalle
**Efecto:**

-   `stock_reservado += cantidad`
-   `stock_disponible` recalculado
-   `stock_actual` sin cambios

#### `liberarStock($cantidad, $codSucursal)`

**Cuándo:** Al cancelar OS o eliminar detalle
**Efecto:**

-   `stock_reservado -= cantidad`
-   `stock_disponible` recalculado
-   `stock_actual` sin cambios

#### `descontarStock($cantidad, $codSucursal)`

**Cuándo:** Al facturar OS
**Efecto:**

-   `stock_actual -= cantidad`
-   `stock_reservado -= cantidad`
-   Stock físicamente descontado

### 4.3 Validaciones de Stock

**Antes de reservar:**

```php
$stockDisponible = $stock_actual - $stock_reservado;
if ($cantidad > $stockDisponible) {
    return false; // Insuficiente
}
```

**Control de stock mínimo:**

```php
if ($stockDisponible < $stock_minimo) {
    // Generar alerta de reposición
}
```

---

## 5. Sistema de Promociones

### 5.1 Aplicación Automática

Cuando se agrega un artículo **manualmente** (no del presupuesto):

1. Sistema consulta: `Promocion::getDescuentoVigente($codArticulo)`
2. Verifica si existe promoción activa y vigente
3. Si existe:
    - Aplica `porcentaje_descuento` automáticamente
    - Calcula `monto_descuento`
    - Recalcula `subtotal`, `impuestos`, `total`
    - Muestra notificación: "¡Promoción aplicada! Descuento del X% aplicado"

### 5.2 Edición Manual de Descuentos

-   Campo `porcentaje_descuento` editable
-   Rango permitido: 0-100%
-   Recalcula automáticamente al cambiar valor
-   Aplica tanto a items del presupuesto como manuales

---

## 6. Interfaz de Usuario

### 6.1 Formulario de Creación

#### Sección 1: Información de la Orden

```
┌─────────────────────────────────────────────────────┐
│  📋 Información de la Orden                         │
├─────────────────────────────────────────────────────┤
│  Presupuesto de Venta: [Select - Aprobados]        │
│  ℹ️ Seleccione un presupuesto aprobado...          │
│                                                     │
│  Cliente: [Juan Pérez] 🔒                          │
│  Mecánico Asignado: [Select empleados]             │
│  Sucursal: [Sucursal Central] 🔒                   │
│                                                     │
│  Fecha Inicio: [08/11/2025]                        │
│  Fecha Est. Finalización: [15/11/2025]             │
│  Estado: [Pendiente ▼]                             │
│                                                     │
│  Diagnóstico: [#5 - Pastillas gastadas] 🔒        │
│  Recepción: [#3 - Toyota ABC123] 🔒                │
│                                                     │
│  Observaciones Técnicas: [Textarea]                │
│  Observaciones Internas: [Textarea]                │
│                                                     │
│  Total: [Gs. 1.545.000] 🔒                         │
└─────────────────────────────────────────────────────┘
```

#### Sección 2: Detalle de Artículos

```
┌───────────────────────────────────────────────────────────────────────────┐
│  🛒 Detalle de Artículos                                                  │
│  Artículos del presupuesto. Puede agregar artículos adicionales...       │
├───────────────────────────────────────────────────────────────────────────┤
│  ▼ Juego de pastillas de freno                         🗑️  ⬆️  ⬇️       │
│  ┌─────────────────────────────────────────────────────────────────────┐ │
│  │ Artículo: [Juego de pastillas de freno ▼] 🔒                       │ │
│  │ 🔒 Del presupuesto (no editable)                                    │ │
│  │                                                                     │ │
│  │ Cant.: [1] u 🔒   Cant. Usada: [0] u   Precio Unit.: [Gs. 450.000]│ │
│  │                                                                     │ │
│  │ % Desc.: [10] %   Subtotal: [Gs. 405.000] 🔒                      │ │
│  │ Total: [Gs. 445.500] 🔒   Stock Reservado: ☑️                     │ │
│  └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│  [+ Agregar artículo]                                                     │
│                                                                           │
│  ▼ Filtro de aceite (Artículo adicional)                🗑️  ⬆️  ⬇️      │
│  ┌─────────────────────────────────────────────────────────────────────┐ │
│  │ Artículo: [Filtro de aceite ▼] ✏️                                  │ │
│  │ 🆕 Artículo adicional                                               │ │
│  │                                                                     │ │
│  │ Cant.: [2] u   Cant. Usada: [0] u   Precio Unit.: [Gs. 75.000]    │ │
│  │                                                                     │ │
│  │ % Desc.: [15] %   Subtotal: [Gs. 127.500] 🔒                      │ │
│  │ Total: [Gs. 140.250] 🔒   Stock Reservado: ☐                      │ │
│  │                                                                     │ │
│  │ ℹ️ ¡Promoción aplicada! Descuento del 15%                          │ │
│  └─────────────────────────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────────────────────────┘

[Cancelar]  [Crear] 🔵
```

### 6.2 Estados Visuales

**🔒 Campo bloqueado (del presupuesto):**

-   `disabled(true)` + `dehydrated(true)`
-   Color gris, no editable
-   Valor se guarda en BD

**✏️ Campo editable (artículo nuevo):**

-   Fondo blanco, editable
-   Validaciones activas

**☑️ Stock reservado:**

-   Toggle activado
-   Indica que el stock ya fue reservado

**☐ Stock no reservado:**

-   Toggle desactivado
-   Pendiente de reserva

---

## 7. Observers y Eventos

### 7.1 OrdenServicioDetalleObserver

#### `created(OrdenServicioDetalle $detalle)`

**Trigger:** Después de crear un nuevo detalle
**Acción:**

```php
if (!$detalle->stock_reservado && $detalle->ordenServicio) {
    $detalle->reservarStock();
}
```

#### `updated(OrdenServicioDetalle $detalle)`

**Trigger:** Después de actualizar un detalle
**Acción:**

-   Si cambió la `cantidad` y `stock_reservado == true`:
    -   Calcular diferencia
    -   Si aumentó: reservar stock adicional
    -   Si disminuyó: liberar exceso

#### `deleting(OrdenServicioDetalle $detalle)`

**Trigger:** Antes de eliminar un detalle
**Acción:**

```php
if ($detalle->stock_reservado) {
    $detalle->liberarStock();
}
```

---

## 8. Validaciones y Reglas de Negocio

### 8.1 Validaciones de Creación

✅ **Presupuesto requerido**

-   No se puede crear OS sin presupuesto
-   Solo presupuestos en estado "Aprobado"

✅ **Mínimo 1 artículo**

-   Validación: `minItems(1)` en Repeater
-   Mensaje: "Debe agregar al menos un artículo a la orden de servicio"

✅ **Artículo requerido en cada detalle**

-   `cod_articulo` NOT NULL en BD
-   Campo obligatorio en formulario

✅ **Precio unitario requerido**

-   `precio_unitario` NOT NULL en BD
-   Carga automática desde artículo
-   Editable manualmente si falla carga

✅ **Usuario con sucursal asignada**

-   `auth()->user()->cod_sucursal` debe existir
-   Asignado en creación de usuario

### 8.2 Validaciones de Stock

✅ **Validación en tiempo real al seleccionar artículo**

-   Al seleccionar un artículo adicional, el sistema consulta inmediatamente el stock disponible
-   Muestra notificación informativa: "Hay X unidades disponibles de [Artículo]"
-   Si no existe registro de stock, muestra advertencia: "No existe registro de stock para [Artículo] en esta sucursal"

✅ **Validación en tiempo real al ingresar cantidad**

-   Al modificar la cantidad, el sistema valida contra el stock disponible en tiempo real (debounce 300ms)
-   SI cantidad > stock_disponible:
    -   Muestra notificación persistente de advertencia: "Stock insuficiente. Solo hay X unidades disponibles de [Artículo] en esta sucursal. Solicitado: Y"
    -   Permite continuar (la OS se creará con advertencia en el paso de guardado)
-   SI cantidad <= stock_disponible:
    -   Muestra notificación de éxito: "Stock disponible. Hay X unidades disponibles"

✅ **Validación al guardar (reserva final)**

-   Verificar stock disponible antes de reservar
-   Permitir creación con advertencia si insuficiente
-   Marcar `stock_reservado = false` si falla

✅ **Stock reservado antes de facturar**

-   No se puede descontar stock no reservado
-   Validar en proceso de facturación

### 8.3 Cálculos Automáticos

**Fórmulas:**

```
monto_descuento = (cantidad × precio_unitario) × (porcentaje_descuento / 100)
subtotal = (cantidad × precio_unitario) - monto_descuento
monto_impuesto = subtotal × (porcentaje_impuesto / 100)
total = subtotal + monto_impuesto
```

**Recalculo automático cuando cambia:**

-   cantidad
-   precio_unitario
-   porcentaje_descuento
-   porcentaje_impuesto

---

## 9. Seguridad y Auditoría

### 9.1 Campos de Auditoría

Todas las tablas incluyen:

-   `usuario_alta`: Usuario que creó el registro
-   `fec_alta`: Timestamp de creación
-   `usuario_mod`: Último usuario que modificó
-   `fec_mod`: Timestamp de última modificación

### 9.2 Trazabilidad

**Relación Presupuesto → OS → Detalle:**

```
presupuesto_ventas.id
    ↓
orden_servicios.presupuesto_venta_id
    ↓
orden_servicio_detalles.orden_servicio_id
    ↓
orden_servicio_detalles.presupuesto_venta_detalle_id
```

Permite rastrear desde qué presupuesto y detalle original proviene cada item de la OS.

---

## 10. Notificaciones al Usuario

### 10.1 Notificaciones de Éxito

**OS creada exitosamente:**

```
✅ Orden de servicio creada
El stock ha sido reservado correctamente.
```

**Promoción aplicada:**

```
✅ ¡Promoción aplicada!
Descuento del 15% aplicado por promoción vigente
```

**Stock disponible confirmado:**

```
✅ Stock disponible
Hay X unidades disponibles
```

### 10.2 Notificaciones Informativas

**Stock disponible al seleccionar artículo:**

```
ℹ️ Stock disponible
Hay X unidades disponibles de [Artículo]
```

### 10.3 Notificaciones de Advertencia

**Stock insuficiente en tiempo real:**

```
⚠️ Stock insuficiente
Solo hay X unidades disponibles de [Artículo] en esta sucursal. Solicitado: Y
```

(Nota: Esta notificación es persistente y permanece visible)

**Sin registro de stock:**

```
⚠️ Sin stock registrado
No existe registro de stock para [Artículo] en esta sucursal
```

**Stock parcialmente reservado al guardar:**

```
⚠️ Orden de servicio creada con advertencia
No se pudo reservar todo el stock:

✅ Filtro de aceite: 10 unidades reservadas
❌ Pastillas de freno: Solicitado 20, Disponible 10
✅ Aceite motor: 5 unidades reservadas
```

### 10.4 Notificaciones de Error

**Sin artículos:**

```
❌ Error de validación
Debe agregar al menos un artículo a la orden de servicio.
Seleccione un presupuesto o agregue artículos manualmente.
```

---

## 11. Casos de Uso

### Caso de Uso 1: OS Estándar desde Presupuesto

**Actor:** Recepcionista
**Pre-condiciones:**

-   Existe presupuesto aprobado
-   Usuario tiene sucursal asignada
-   Hay stock disponible

**Flujo:**

1. Accede a "Orden de Servicio" → "Crear"
2. Selecciona presupuesto #15
3. Sistema carga automáticamente todos los datos
4. Asigna mecánico "Pedro González"
5. Establece fecha estimada: 15/11/2025
6. Agrega observación técnica: "Cliente reporta ruido en frenos"
7. Click en "Crear"
8. Sistema reserva stock exitosamente
9. Muestra confirmación de creación

**Post-condiciones:**

-   OS creada con estado "Pendiente"
-   Stock reservado en sucursal
-   Detalles copiados del presupuesto

### Caso de Uso 2: OS con Artículos Adicionales

**Actor:** Mecánico Jefe
**Pre-condiciones:**

-   Existe presupuesto aprobado
-   Durante el trabajo se necesitan repuestos adicionales

**Flujo:**

1. Crea OS desde presupuesto #20
2. Sistema carga 3 artículos del presupuesto
3. Click en "Agregar artículo"
4. Selecciona "Kit de empaques"
5. Sistema carga precio Gs. 180.000
6. Sistema detecta promoción 10% y la aplica
7. Muestra notificación de promoción
8. Edita cantidad a 2 unidades
9. Sistema recalcula total automáticamente
10. Click en "Crear"
11. Sistema reserva stock de 4 artículos (3 + 1)

**Post-condiciones:**

-   OS con artículos del presupuesto + adicionales
-   Promoción aplicada correctamente
-   Todo el stock reservado

### Caso de Uso 3: OS con Stock Insuficiente

**Actor:** Recepcionista
**Pre-condiciones:**

-   Presupuesto solicita 20 pastillas
-   Solo hay 10 disponibles en sucursal

**Flujo:**

1. Crea OS desde presupuesto #25
2. Click en "Crear"
3. Sistema intenta reservar stock
4. Falla en "Pastillas de freno" (insuficiente)
5. Muestra notificación de advertencia con detalle
6. OS se crea de todos modos
7. Pastillas marcadas con `stock_reservado = false`
8. Otros artículos reservados exitosamente

**Post-condiciones:**

-   OS creada pero incompleta
-   Requiere gestión de reposición
-   Alerta visible en sistema

---

## 12. Integraciones

### 12.1 Con Módulo de Presupuestos

-   Lee presupuestos aprobados
-   Copia datos de cabecera y detalles
-   Mantiene trazabilidad por ID

### 12.2 Con Módulo de Diagnóstico

-   Vincula diagnóstico mecánico
-   Hereda datos del presupuesto

### 12.3 Con Módulo de Recepción

-   Vincula recepción de vehículo
-   Hereda datos del presupuesto

### 12.4 Con Módulo de Stock

-   Lee stock disponible por sucursal
-   Reserva stock en `existe_stock`
-   Actualiza cantidades reservadas

### 12.5 Con Módulo de Promociones

-   Consulta promociones vigentes
-   Aplica descuentos automáticamente
-   Solo para artículos nuevos (no del presupuesto)

### 12.6 Con Módulo de Usuarios

-   Lee sucursal del usuario
-   Asigna mecánicos disponibles
-   Registra auditoría

---

## 13. Consideraciones Técnicas

### 13.1 Performance

**Optimizaciones implementadas:**

-   `->preload()` en Selects de artículos (carga anticipada)
-   `->searchable()` para búsqueda eficiente
-   `->with(['detalles.articulo'])` eager loading al cargar presupuesto
-   Índices en campos FK

**Debounce en campos reactivos:**

-   `cantidad`: 300ms
-   Evita cálculos excesivos mientras el usuario escribe

### 13.2 Filament Components

**Campos con estado especial:**

-   `->live()` / `->reactive()`: Actualización en tiempo real
-   `->disabled()` con `->dehydrated()`: Bloqueado pero guarda valor
-   `->readOnly()`: Solo lectura, siempre guarda
-   `->afterStateUpdated()`: Callbacks de cambio de estado

**Repeater configuration:**

-   `->relationship('detalles')`: Gestión automática de relación
-   `->minItems(1)`: Validación mínima
-   `->collapsible()`: Permite colapsar items
-   `->itemLabel()`: Etiqueta personalizada por item

### 13.3 Transacciones

**Creación de OS (implícita en Eloquent):**

```php
DB::transaction(function() {
    // 1. Crear orden_servicios
    $os = OrdenServicio::create([...]);

    // 2. Crear orden_servicio_detalles (via relationship)
    $os->detalles()->createMany([...]);

    // 3. Observer dispara reservarStock()
    // Si falla, rollback automático
});
```

---

## 14. Testing

### 14.1 Casos de Prueba Funcionales

**Test 1: Creación estándar**

-   ✅ Crear OS desde presupuesto válido
-   ✅ Verificar datos copiados correctamente
-   ✅ Verificar stock reservado

**Test 2: Artículos adicionales**

-   ✅ Agregar artículo manual
-   ✅ Verificar carga automática de precio
-   ✅ Verificar aplicación de promoción

**Test 3: Validaciones**

-   ✅ Intentar crear sin presupuesto (debe fallar)
-   ✅ Intentar crear sin artículos (debe fallar)
-   ✅ Intentar crear sin cod_articulo (debe fallar)

**Test 4: Stock insuficiente**

-   ✅ Crear con stock parcial
-   ✅ Verificar advertencia mostrada
-   ✅ Verificar OS creada pero marcada

**Test 5: Edición de descuentos**

-   ✅ Editar porcentaje_descuento
-   ✅ Verificar recalculo de totales
-   ✅ Guardar y verificar valores

### 14.2 Casos de Prueba de Stock

**Test 6: Reserva exitosa**

-   ✅ Stock disponible >= cantidad
-   ✅ stock_reservado incrementado
-   ✅ stock_disponible decrementado

**Test 7: Liberación al cancelar**

-   ✅ Cancelar OS
-   ✅ stock_reservado decrementado
-   ✅ stock_disponible restaurado

**Test 8: Descuento al facturar**

-   ✅ Facturar OS
-   ✅ stock_actual decrementado
-   ✅ stock_reservado decrementado

---

## 15. Mejoras Futuras Sugeridas

### Corto Plazo

1. ✨ **Impresión de OS**: Generar PDF con detalles completos
2. ✨ **Historial de cambios**: Log de modificaciones de estado
3. ✨ **Alertas de stock mínimo**: Notificación cuando stock < mínimo
4. ✨ **Firma digital**: Captura de firma del cliente

### Mediano Plazo

5. ✨ **App móvil para mecánicos**: Actualización de estado desde taller
6. ✨ **Estimación automática de tiempo**: ML basado en histórico
7. ✨ **Notificaciones al cliente**: SMS/Email de progreso
8. ✨ **Galería de fotos**: Antes/después del servicio

### Largo Plazo

9. ✨ **BI y Reportes**: Dashboard de rendimiento por mecánico
10. ✨ **Integración con proveedores**: Pedido automático de stock
11. ✨ **Sistema de turnos**: Agenda de mecánicos
12. ✨ **Gestión de garantías**: Control de trabajos garantizados

---

## 16. Glosario

-   **OS**: Orden de Servicio
-   **Presupuesto**: Cotización aprobada por el cliente
-   **Stock Reservado**: Cantidad apartada para OS pendientes
-   **Stock Actual**: Cantidad física en sucursal
-   **Stock Disponible**: Actual - Reservado
-   **Dehydrated**: Campo que envía su valor aunque esté deshabilitado
-   **Observer**: Clase que escucha eventos del modelo
-   **Eager Loading**: Carga anticipada de relaciones
-   **FK**: Foreign Key (Llave foránea)

---

## Versión del Documento

-   **Versión**: 1.0
-   **Fecha**: 08 de Noviembre de 2025
-   **Autor**: Sistema SysAltoPiston
-   **Estado**: Implementado y Funcionando

---

## Contacto y Soporte

Para consultas sobre esta especificación o el módulo implementado, contactar al equipo de desarrollo.
