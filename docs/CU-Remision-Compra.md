# CASO DE USO: REGISTRO DE REMISIONES DE COMPRA

## Descripción Básica
Este caso de uso gestiona el registro de Notas de Remisión (Guías de Remisión) para la recepción de mercadería comprada, con validación fiscal mediante timbrado del proveedor y soporte para recepción parcial de facturas.

## Actores Relacionados
- **Auxiliar de Compra**: Registra las remisiones de recepción de mercadería
- **Jefe de Compra**: Puede anular remisiones registradas

## Pre Condiciones
1. Poseer perfil de usuario con permisos adecuados
2. Conexión a base de datos PostgreSQL
3. Tener registradas las siguientes tablas:
   - `st_articulos` (artículos del inventario)
   - `cm_compras_cabecera` (facturas de compra)
   - `cm_compras_detalle` (ítems de facturas)
   - `proveedores` (proveedores registrados)
   - `personas_pro` (datos de personas/empresas proveedoras)
   - `timbrado_proveedor` (timbrados fiscales de proveedores)
   - `st_existencia_art` (existencias por almacén)
   - `st_remision_cabecera` (cabecera de remisiones)
   - `st_remision_detalle` (detalle de ítems recibidos)

---

## FLUJO DE EVENTOS

### A. FLUJO BÁSICO - LISTAR REMISIONES

1. El usuario selecciona en el menú el ítem **"Nota de Remisión"**
2. El sistema abre la interfaz **"Listado de Guía de Remisión"**
3. El sistema llama conexión a la base de datos
4. El sistema consulta la tabla `st_remision_cabecera` con sus relaciones:
   - `compraCabecera` (relación por `compra_cabecera_id`)
   - `factura` (relación compuesta por `tip_factura`, `ser_factura`, `nro_factura`)
   - `proveedor` (relación por `cod_proveedor`)
5. El sistema presenta las siguientes columnas en la tabla:
   - **N° Remisión**: `numero_remision` (formato 7 dígitos: 0000001)
   - **Serie**: `ser_remision`
   - **Fecha**: `fecha_remision`
   - **N° Factura**: Muestra `ser_factura-nro_factura` o desde la relación `compraCabecera`
   - **Proveedor**: Nombre desde relación directa `proveedor` o desde `compraCabecera.proveedor`
   - **Estado**: P (Pendiente), A (Aprobada), N (Anulada)
   - **Timbrado**: Número de timbrado fiscal
6. El sistema permite filtrar, buscar y paginar los registros

---

### B. CREAR NOTA DE REMISIÓN

#### B.1. Inicialización del Formulario

1. El Auxiliar de Compra presiona el botón **"Crear Guía de Remisión"**
2. El sistema redirecciona a la interfaz de registro
3. El sistema ejecuta el método `mount()` y realiza las siguientes acciones:
   - Recupera datos del usuario autenticado (trait `WithSucursalData`):
     - `cod_sucursal`: Código de sucursal del usuario
     - `nombre_sucursal`: Nombre de la sucursal
   - Establece valores por defecto:
     - **Tipo**: "REM" (Remisión)
     - **Serie**: "001-001"
     - **Sucursal Destino**: Nombre de la sucursal del usuario (campo deshabilitado)
     - **Usuario Carga**: Nombre del usuario conectado (campo deshabilitado)
   - Recupera el `almacen_id` asociado a la sucursal

#### B.2. Sección: Vinculación con Factura y Proveedor

**El sistema presenta DOS opciones MUTUAMENTE EXCLUYENTES:**

##### Opción A: Con Factura de Compra

4. El sistema llama conexión a la base de datos
5. El sistema consulta `cm_compras_cabecera` con las siguientes condiciones:
   - **Filtro**: Solo facturas con artículos pendientes de recepción
   - **Calcula**: `porcentaje_recepcion` mediante accessor en el modelo
   - **Joins**: Incluye relaciones con `proveedor.personas_pro` y `detalles`
6. El sistema carga el combo **"Factura de Compra (Opcional)"** con formato:
   ```
   Factura 001-003-1 | Nombre Proveedor | 27/04/2026 [50% recibido]
   ```
7. El Auxiliar de Compra **selecciona una factura** del combo
8. El sistema ejecuta `afterStateUpdated` y realiza:
   - Recupera datos de la factura seleccionada con sus relaciones
   - **Auto-completa** los siguientes campos (deshabilitados):
     - `cod_proveedor`: Código del proveedor de la factura
     - **Información del Proveedor**: "{Nombre completo/Razón Social} - RUC: {RUC}"
   - **Establece campos compuestos de factura** (campos ocultos):
     - `tip_factura`: Tipo de comprobante (ej: "FAC")
     - `ser_factura`: Serie del comprobante (ej: "001-003")
     - `nro_factura`: Número del comprobante (ej: "1")
   - **Carga la grilla de ítems** automáticamente:
     - Consulta `cm_compras_detalle` filtrado por `id_compra_cabecera`
     - Por cada detalle, calcula mediante accessor `getCantidadRecibidaAttribute()`:
       - Busca en `st_remision_detalle` las cantidades ya recepcionadas
       - Soporta búsqueda por `compra_cabecera_id` O por campos compuestos
       - Excluye remisiones anuladas (`estado != 'N'`)
     - Calcula `cantidad_pendiente = cantidad - cantidad_recibida`
     - Solo incluye ítems con `cantidad_pendiente > 0`
   - **Deshabilita** el selector de Proveedor (se establece desde la factura)
   - **Deshabilita** los campos de artículo en la grilla (vienen de la factura)

##### Opción B: Sin Factura (Proveedor Manual)

9. El Auxiliar de Compra **selecciona un proveedor** del combo **"Proveedor"**
10. El sistema ejecuta `afterStateUpdated` y realiza:
    - Busca en tabla `proveedores` con join a `personas_pro`
    - **Auto-completa** el campo **Información del Proveedor**
    - **Limpia** el campo `compra_cabecera_id` (exclusión mutua)
    - **Habilita** la grilla de ítems para ingreso manual
    - **Deshabilita** el selector de Factura
11. La grilla de ítems permite:
    - Agregar/eliminar filas manualmente
    - Seleccionar artículos desde combo con búsqueda
    - Ingresar cantidades libremente

#### B.3. Sección: Datos del Comprobante

12. El sistema presenta campos en Grid(4):
    - **Tipo**: "REM" (deshabilitado, valor fijo)
    - **Serie**: "001-001" (editable)
    - **Número**: Campo numérico con validación especial
    - **Timbrado**: Selector con relaciones

##### Ingreso y Validación del Número de Remisión

13. El Auxiliar de Compra ingresa el **Número de Remisión**
14. El sistema aplica transformación automática:
    - **Formatea a 7 dígitos** con ceros a la izquierda
    - Ejemplo: `4` → `0000004`, `123` → `0000123`
15. El sistema ejecuta validación mediante closure en `rules()`:
    - Verifica que no exista duplicado en la tabla `st_remision_cabecera` buscando:
      - El mismo número de remisión
      - Con la misma serie
      - Para el mismo proveedor
    - **Validación compuesta**: numero + serie + proveedor (permite reutilizar números con diferentes proveedores)
16. Si existe duplicado:
    - El sistema emite **Notification** con:
      - Título: "❌ Número de Remisión Duplicado"
      - Cuerpo: "El número **0000004** ya está registrado para este proveedor y serie. Por favor, ingrese un número diferente."
      - Tipo: `danger()` + `persistent()`
    - El sistema ejecuta `$fail()` para marcar el campo como inválido
    - **NO permite continuar** hasta corregir

##### Validación y Carga de Timbrado Fiscal

17. El sistema ejecuta `afterStateUpdated` del campo **Número**:
18. El sistema llama conexión
19. El sistema consulta tabla `timbrado_proveedor` buscando un timbrado que cumpla:
    - Corresponda al proveedor seleccionado
    - La serie del timbrado coincida con la serie ingresada
    - El número ingresado esté dentro del rango permitido (entre número inicial y número final del timbrado)
    - El timbrado esté activo
20. Si encuentra un timbrado válido:
    - El sistema **auto-completa** el campo "Timbrado" con `num_timbrado`
    - Notifica: "✅ Timbrado cargado: {num_timbrado}"
21. Si NO encuentra timbrado:
    - El sistema limpia el campo "Timbrado"
    - Notifica: "⚠️ Timbrado no encontrado para este número y serie"
22. El Auxiliar puede presionar el botón **[+]** junto a "Timbrado":
    - El sistema abre modal "Registrar Nuevo Timbrado"
    - Presenta formulario con campos:
      - Proveedor (prellenado, deshabilitado)
      - Serie de Timbrado
      - Número de Timbrado
      - Fecha Inicial
      - Fecha de Vencimiento
      - Número Inicial
      - Número Final
    - Al guardar, inserta en tabla `timbrado_proveedor`
    - Recarga el selector de Timbrado

#### B.4. Otros Campos del Formulario

23. El sistema presenta en Grid(3):
    - **Fecha de Remisión**: DatePicker (requerido)
    - **Sucursal Destino**: Texto deshabilitado con nombre de sucursal
    - **Usuario Carga**: Texto deshabilitado con nombre de usuario
24. El sistema muestra como **Placeholder** (no editable):
    - **Fecha Alta**: Se establece automáticamente al guardar con `now()`

#### B.5. Sección: Ítems de la Remisión (Grilla)

25. El sistema presenta un **Repeater** (tabla editable) con:

**Si viene de FACTURA (modo bloqueado):**
- **Artículo**: Selector deshabilitado (valor pre-cargado)
- **Artículo Nombre**: Texto deshabilitado
- **Cant. Facturada**: Número deshabilitado
- **Cant. Ya Recibida**: Número deshabilitado (acumulado de remisiones anteriores)
- **Cant. Pendiente**: Número deshabilitado (calculado)
- **Cant. Recibida**: **Campo editable** para ingresar cantidad a recepcionar

**Si es MANUAL (sin factura):**
- **Artículo**: Selector editable con búsqueda en tabla `st_articulos`
- **Artículo Nombre**: Se completa automáticamente al seleccionar
- **Cant. Recibida**: Campo numérico libre

26. El Auxiliar de Compra ingresa **Cantidad Recibida** para cada ítem
27. El sistema NO permite agregar/eliminar filas si viene de factura
28. El sistema SÍ permite agregar/eliminar filas si es ingreso manual

---

### C. GUARDAR REMISIÓN

#### C.1. Validaciones Pre-Guardado

1. El Auxiliar presiona el botón **"Crear"**
2. El sistema valida campos obligatorios:
   - ✓ Proveedor (directamente o desde factura)
   - ✓ Serie de remisión
   - ✓ Número de remisión
   - ✓ Timbrado
   - ✓ Fecha de remisión
   - ✓ Al menos un ítem en la grilla
   - ✓ Cantidad recibida > 0 en cada ítem
3. **Validación adicional si viene de factura**:
   - Por cada ítem, valida que:
     ```
     cantidad_recibida <= cantidad_pendiente
     ```
   - Si excede, emite notification error

#### C.2. Validación de Duplicado (Server-Side)

4. El sistema ejecuta `handleRecordCreation()` en `CreateGuiaRemision.php`
5. Determina el `cod_proveedor`:
   - Si está en `$data['cod_proveedor']`: lo usa directamente
   - Si está en `$data['compra_cabecera_id']`: lo obtiene de la factura
6. El sistema realiza **validación server-side**:
   ```php
   $existe = GuiaRemisionCabecera::where('numero_remision', $numero)
       ->where('ser_remision', $serie)
       ->where('cod_proveedor', $proveedorId)
       ->exists();
   ```
7. Si existe duplicado:
   - Emite Notification: "❌ Número de Remisión Duplicado"
   - Ejecuta `$this->halt()` para detener el proceso
   - **NO guarda** ningún dato
   - Mantiene al usuario en el formulario

#### C.3. Guardado de Datos (Transacción DB)

8. El sistema inicia **transacción de base de datos** (`DB::transaction`)
9. El sistema llama conexión
10. El sistema **INSERT en tabla `st_remision_cabecera`** con datos:
    ```php
    [
        'compra_cabecera_id' => $data['compra_cabecera_id'] ?? null,
        'tip_factura' => $data['tip_factura'] ?? null,
        'ser_factura' => $data['ser_factura'] ?? null,
        'nro_factura' => $data['nro_factura'] ?? null,
        'cod_proveedor' => $proveedorId,
        'almacen_id' => $data['almacen_id'] ?? $cod_sucursal,
        'tipo_comprobante' => 'REM',
        'ser_remision' => $data['ser_remision'],
        'numero_remision' => $data['numero_remision'], // 7 dígitos
        'timbrado' => $data['timbrado'],
        'fecha_remision' => $data['fecha_remision'],
        'cod_sucursal' => $data['cod_sucursal'],
        'usuario_alta' => auth()->user()->name,
        'fec_alta' => now(),
        'estado' => 'P', // P: Pendiente
    ]
    ```

#### C.4. Guardado de Detalles y Actualización de Stock

11. Por cada ítem en `$data['detalles']`:
12. El sistema llama conexión
13. El sistema **INSERT en tabla `st_remision_detalle`**:
    ```php
    [
        'remision_id' => $cabecera->id,
        'cod_articulo' => $item['articulo_id'],
        'cantidad_recibida' => $item['cantidad_recibida'],
    ]
    ```
14. El sistema llama conexión
15. El sistema busca en tabla `st_existencia_art` el registro que corresponda:
    - Al artículo recibido
    - En el almacén de destino
16. Si existe el registro:
    - **UPDATE**: Suma `cantidad_recibida` al stock actual
    ```php
    stock_actual += $cantidad_recibida
    ```
17. Si NO existe el registro:
    - **INSERT**: Crea nueva existencia
    ```php
    [
        'cod_articulo' => $articulo,
        'almacen_id' => $almacen,
        'stock_actual' => $cantidad_recibida,
    ]
    ```

#### C.5. Actualización de Porcentaje de Recepción (Solo si hay factura)

18. Si `$data['compra_cabecera_id']` está presente:
19. El sistema llama conexión
20. El sistema busca la factura en tabla `cm_compras_cabecera` usando **relación compuesta**:
    - Tipo de comprobante coincide con el registrado
    - Serie de comprobante coincide con el registrado
    - Número de comprobante coincide con el registrado
21. El sistema recalcula `porcentaje_recepcion` de la factura:
    - Suma total de cantidades facturadas de todos los ítems
    - Suma total de cantidades recibidas de todos los ítems
    - Calcula: `(total_recibido / total_facturado) * 100`
22. El sistema determina el mensaje según porcentaje:
    - Si `porcentaje < 100`: "⚠️ Recepción parcial: {porcentaje}%"
    - Si `porcentaje >= 100`: "✅ Factura completamente recepcionada"

#### C.6. Confirmación y Redirección

23. El sistema **COMMIT** de la transacción
24. El sistema emite **Notification de éxito**:
    - Título: "✅ Remisión guardada"
    - Cuerpo (si hay factura): "Factura {serie}-{numero}: {porcentaje}% recepcionado"
    - Tipo: `success()`
25. El sistema registra en LOG:
    ```
    Remisión creada exitosamente
    id: 34
    compra_cabecera_id: 20
    cod_proveedor: 1
    numero: 0000004
    ```
26. El sistema redirecciona a la interfaz **"Listado de Guía de Remisión"**

---

## FLUJOS ALTERNATIVOS

### D. ANULAR REMISIÓN

1. El Jefe de Compra ingresa filtros de búsqueda en el listado
2. El sistema filtra los datos de la tabla en tiempo real
3. El Jefe de Compra selecciona una remisión y presiona **"Anular"**
4. El sistema emite mensaje de confirmación:
   - "¿Está seguro de anular esta remisión?"
5. El Jefe de Compra confirma
6. El sistema llama conexión
7. El sistema actualiza la tabla `st_remision_cabecera`:
   - Cambia el estado a 'N' (Anulada)
   - Registra el usuario que realizó la modificación
   - Registra la fecha y hora de modificación
8. El sistema llama conexión
9. El sistema **revierte el stock** en tabla `st_existencia_art`:
   - Por cada ítem de la remisión:
     - Localiza el registro del artículo en el almacén correspondiente
     - Resta la cantidad recibida del stock actual
10. El sistema emite mensaje: "✅ Remisión anulada correctamente"
11. El sistema actualiza la grilla del listado

---

## FLUJOS DE EXCEPCIÓN

### E.1. Número de Remisión Duplicado
- **Trigger**: Usuario ingresa número ya existente para el mismo proveedor y serie
- **Acción**: Sistema muestra notification roja persistente con mensaje claro
- **Resultado**: NO se guarda, usuario debe corregir

### E.2. Timbrado No Encontrado
- **Trigger**: Número ingresado no está en el rango de ningún timbrado activo
- **Acción**: Sistema limpia campo timbrado y muestra warning
- **Resultado**: Usuario debe registrar nuevo timbrado o corregir número/serie

### E.3. Cantidad a Recepcionar Excede Pendiente
- **Trigger**: En modo con factura, usuario ingresa cantidad > cantidad_pendiente
- **Acción**: Sistema valida y muestra error específico por ítem
- **Resultado**: NO se guarda hasta corregir

### E.4. Sin Conexión a Base de Datos
- **Trigger**: Pérdida de conexión durante el proceso
- **Acción**: Sistema ejecuta ROLLBACK de transacción
- **Resultado**: Ningún dato se guarda parcialmente

### E.5. Proveedor Sin Timbrado Registrado
- **Trigger**: Proveedor seleccionado no tiene timbrados en sistema
- **Acción**: Sistema permite registrar nuevo timbrado vía modal
- **Resultado**: Usuario registra timbrado y continúa con la remisión

---

## POST CONDICIONES

### Exitosa
1. Registro insertado en tabla `st_remision_cabecera` con estado 'P'
2. Registros insertados en tabla `st_remision_detalle` para cada ítem
3. Stock actualizado en tabla `st_existencia_art` por almacén
4. Porcentaje de recepción recalculado en la factura (si aplica)
5. Usuario redirigido a listado con mensaje de confirmación
6. Log registrado en `storage/logs/laravel.log`

### Fallida
1. Ningún dato guardado (transacción revertida)
2. Usuario permanece en formulario con datos intactos
3. Mensaje de error específico mostrado
4. Log de error registrado

---

## TABLAS DE BASE DE DATOS UTILIZADAS

### Lectura (SELECT)
| Tabla | Propósito |
|-------|-----------|
| `cm_compras_cabecera` | Obtener facturas pendientes de recepción |
| `cm_compras_detalle` | Obtener ítems de factura y cantidades |
| `proveedores` | Selector de proveedores |
| `personas_pro` | Datos de proveedor (nombre, RUC) |
| `st_articulos` | Selector de artículos |
| `timbrado_proveedor` | Validación y carga de timbrado fiscal |
| `st_remision_detalle` | Cálculo de cantidades ya recibidas |
| `st_existencia_art` | Verificar stock actual |

### Escritura (INSERT/UPDATE)
| Tabla | Operación | Momento |
|-------|-----------|---------|
| `st_remision_cabecera` | INSERT | Al guardar cabecera |
| `st_remision_detalle` | INSERT | Por cada ítem recibido |
| `st_existencia_art` | UPDATE o INSERT | Al actualizar stock |
| `timbrado_proveedor` | INSERT | Al registrar nuevo timbrado |

---

## CAMPOS CLAVE Y RELACIONES

### Relaciones Compuestas (Nueva Implementación)
La factura se relaciona mediante **3 campos** en lugar de ID:
- `tip_factura` (Tipo: FAC, NCR, etc.)
- `ser_factura` (Serie: 001-003)
- `nro_factura` (Número: 1)

**Ventaja**: Integridad referencial más robusta para validaciones de negocio

### Constraint Unique Compuesto
```sql
CONSTRAINT unique_remision_proveedor_serie_numero 
UNIQUE (cod_proveedor, ser_remision, numero_remision)
```
**Permite**: Reutilizar números para diferentes proveedores

### Campos Auto-Calculados (Accessors)
- `cantidad_recibida` (CompraDetalle): Suma de remisiones para el ítem
- `cantidad_pendiente` (CompraDetalle): Cantidad - cantidad_recibida
- `porcentaje_recepcion` (CompraCabecera): % de la factura recepcionado
- `esta_completamente_recepcionada` (CompraCabecera): Boolean si >= 100%

---

## VALIDACIONES IMPLEMENTADAS

### Cliente-Side (Formulario Filament)
1. Campos requeridos con `->required()`
2. Exclusión mutua entre factura y proveedor con `->disabled()`
3. Formato 7 dígitos con `->mask()` y `->formatStateUsing()`
4. Validación de duplicado con `->rules()` + Notification
5. Campos deshabilitados según contexto con `fn(Get $get)`

### Server-Side (Backend Laravel)
1. Validación duplicado de número en `handleRecordCreation()`
2. Transacciones de BD para atomicidad
3. Try-Catch para manejo de errores
4. Logs detallados para debugging

### Base de Datos (PostgreSQL)
1. Constraints UNIQUE compuestos
2. Foreign Keys con `ON DELETE RESTRICT`
3. NOT NULL en campos críticos
4. Tipos de datos validados (numeric, varchar, date)

---

## ARCHIVOS DEL SISTEMA

### Backend (Laravel)
| Archivo | Responsabilidad |
|---------|----------------|
| `app/Filament/Resources/GuiaRemisionResource.php` | Definición del formulario y tabla |
| `app/Filament/Resources/GuiaRemisionResource/Pages/CreateGuiaRemision.php` | Lógica de creación y validación |
| `app/Models/GuiaRemisionCabecera.php` | Modelo Eloquent con relaciones |
| `app/Models/GuiaRemisionDetalle.php` | Modelo de detalles |
| `app/Models/CompraCabecera.php` | Modelo de factura con accessors |
| `app/Models/CompraDetalle.php` | Modelo de ítem con cálculo de recibido |
| `app/Models/Proveedor.php` | Modelo de proveedor |
| `app/Models/timbradoProv.php` | Modelo de timbrados fiscales |

### Migraciones Ejecutadas
| Archivo | Cambio |
|---------|--------|
| `2026_04_27_184705_add_cod_proveedor_to_remision_cabecera_table.php` | Agrega `cod_proveedor` con FK |
| `2026_04_27_190508_add_timbrado_to_remision_cabecera_table.php` | Agrega `timbrado` |
| `2026_04_27_192831_make_compra_cabecera_id_nullable_in_remision_cabecera.php` | Hace `compra_cabecera_id` nullable |
| `2026_04_27_194808_add_factura_fields_to_remision_cabecera.php` | Agrega campos compuestos de factura |
| `2026_04_27_195538_change_unique_constraint_remision_numero.php` | Cambia constraint a compuesto |

### Memoria del Sistema
| Archivo | Contenido |
|---------|-----------|
| `/memories/repo/error-handling-pattern.md` | Patrón de manejo de errores con Notification |
| `/memories/repo/filament-disabled-fields-pattern.md` | Patrón de campos deshabilitados |

---

## CONSIDERACIONES TÉCNICAS

### Performance
- Uso de `->preload()` en selectores para reducir queries
- Eager loading con `with()` para evitar N+1 queries
- Índices en campos de búsqueda compuestos

### Seguridad
- Validación doble (client + server side)
- Transacciones para integridad de datos
- Trait `WithSucursalData` para datos de usuario
- Permisos de Filament Shield por rol

### UX/UI
- Mensajes de error amigables sin stack traces
- Notifications persistentes para errores críticos
- Helper texts contextuales según estado del formulario
- Auto-completado de campos relacionados
- Tooltips en campos de validación

### Mantenibilidad
- Código documentado con comments
- Logs detallados para debugging
- Separación de responsabilidades (Resource vs Page vs Model)
- Validaciones centralizadas en closures reutilizables

---

## NOTAS DE IMPLEMENTACIÓN

1. **Campos Hidden Duplicados**: Se usan campos `Hidden::make()` además de `Select::make()` para `cod_proveedor` y `compra_cabecera_id` porque `->dehydrated()` no funciona correctamente con selectores disabled en Filament.

2. **Relación Compuesta vs ID**: Se prefiere la relación compuesta (tip/ser/nro) sobre ID simple para validaciones de negocio más robustas.

3. **Patrón de Error Handling**: Se usa `Notification::make()->persistent()->send()` + `$this->halt()` en lugar de `throw Exception` para mejor experiencia de usuario.

4. **Cálculo de Recibido**: El accessor `getCantidadRecibidaAttribute()` en `CompraDetalle` soporta AMBOS métodos (ID y compuesto) para retrocompatibilidad.

5. **Auto-padding de Número**: Se usa `str_pad($value, 7, '0', STR_PAD_LEFT)` para garantizar formato consistente en base de datos.

---

**Documento actualizado**: 27 de Abril de 2026  
**Versión**: 2.0 (Implementación Completa con Timbrado y Relaciones Compuestas)  
**Estado**: ✅ Implementado y Funcional
