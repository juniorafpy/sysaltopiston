# Mejoras Realizadas en ArticuloResource

## Fecha: 11/11/2025

## Resumen de Cambios

### 1. Creación de Tabla Impuestos

-   **Archivo**: `database/migrations/2025_11_11_203509_create_impuestos_table.php`
-   **Estructura**:
    -   `cod_impuesto` (PK)
    -   `descripcion` (string)
    -   `porcentaje` (decimal 5,2)
    -   `activo` (boolean)
-   **Datos Iniciales**:
    -   IVA 10% (porcentaje: 10.00)
    -   IVA 5% (porcentaje: 5.00)
    -   Exenta (porcentaje: 0.00)

### 2. Migración de Campo cod_impuesto en Articulos

-   **Archivo**: `database/migrations/2025_11_11_203541_add_cod_impuesto_to_articulos_table.php`
-   **Campo Agregado**: `cod_impuesto` (nullable, FK a tabla impuestos)
-   **Relación**: Foreign key con `onDelete('set null')`

### 3. Modelo Impuesto

-   **Archivo**: `app/Models/Impuesto.php`
-   **Características**:
    -   Primary Key: `cod_impuesto`
    -   Fillable: descripcion, porcentaje, activo
    -   Casts: porcentaje (decimal:2), activo (boolean)

### 4. Actualización del Modelo Articulos

-   **Archivo**: `app/Models/Articulos.php`
-   **Cambios Realizados**:
    -   ❌ Eliminado: `Spatie\MediaLibrary\HasMedia` interface
    -   ❌ Eliminado: `Spatie\MediaLibrary\InteractsWithMedia` trait
    -   ❌ Eliminado: método `registerMediaCollections()`
    -   ❌ Eliminado: campo 'image' de fillable
    -   ✅ Agregado: campo 'cod_impuesto' a fillable
    -   ✅ Agregado: relación `impuesto()` con modelo Impuesto

### 5. Mejoras en ArticuloResource.php

#### 5.1. Imports Actualizados

-   ❌ Eliminado: `FileUpload`, `ImageColumn`, `Card`, `BooleanColumn`, `BadgeColumn` (mal importado)
-   ✅ Agregado: `Section`, `IconColumn`, `BadgeColumn` (correcto)
-   ✅ Agregado: `App\Models\Impuesto`

#### 5.2. Formulario Mejorado

**Sección 1: Información del Artículo**

-   Icon: `heroicon-o-cube`
-   Descripción mejorada: "Complete los datos básicos del repuesto o artículo"
-   Campos con createOptionForm para Marca y Modelo con labels descriptivos
-   Grid layout de 2 columnas

**Sección 2: Precios y Costos**

-   Icon: `heroicon-o-currency-dollar`
-   Grid de 3 columnas con: Costo, Precio, Margen calculado
-   Grid de 2 columnas con:
    -   **Select IVA** (cod_impuesto): Carga impuestos activos, default=1, con live()
    -   **Placeholder Porcentaje IVA**: Muestra el porcentaje del impuesto seleccionado
-   Campos con `live(onBlur: true)` para cálculos reactivos

**Sección 3: Estado y Auditoría**

-   Sin cambios (mantiene estructura existente)

**❌ Eliminado**: Sección "Imagen del Artículo" completa con FileUpload

#### 5.3. Tabla Mejorada

**Columnas Modificadas**:

-   ❌ Eliminado: `ImageColumn` para imagen
-   ✅ Descripción: ahora tiene `weight('bold')`
-   ✅ Tipo: badge con `color('info')`
-   ✅ Agregado: `impuesto.descripcion` con badge y color condicional:
    -   10% → success (verde)
    -   5% → warning (amarillo)
    -   0% (Exenta) → gray (gris)
-   ✅ Estado: cambió de `BadgeColumn` a `IconColumn` con boolean
    -   trueIcon: 'heroicon-o-check-circle'
    -   falseIcon: 'heroicon-o-x-circle'
    -   trueColor: success
    -   falseColor: danger

**Filtros Agregados**:

-   ✅ Nuevo filtro: IVA (cod_impuesto) con relationship a tabla impuestos

#### 5.4. Acciones de Tabla

-   Sin cambios (mantiene Ver, Editar, Eliminar)

### 6. Páginas Create y Edit Mejoradas

#### CreateArticulo.php

**Botones Personalizados**:

-   ✅ Guardar (Ctrl+S) - verde con icon check
-   ✅ Guardar y Crear Otro (Ctrl+Shift+S) - gris con icon plus-circle
-   ✅ Cancelar - gris con icon x-mark

#### EditArticulo.php

**Botones Personalizados**:

-   ✅ Guardar (Ctrl+S) - verde con icon check
-   ✅ Cancelar - gris con icon x-mark
-   ✅ Header Actions: agregado ViewAction

## Campos del Formulario de Artículos (Completo)

### Información del Artículo

1. **descripcion** (required) - Descripción del repuesto
2. **cod_marca** (required) - Marca del artículo
3. **cod_modelo** (required) - Modelo del artículo
4. **cod_tip_articulo** (required) - Tipo de artículo (Repuesto, Accesorio, etc.)
5. **cod_medida** (optional) - Unidad de medida

### Precios y Costos

6. **costo** (required) - Precio de compra (Gs.)
7. **precio** (required) - Precio de venta (Gs.)
8. **margen** (calculated) - Margen de ganancia calculado automáticamente
9. **cod_impuesto** (required) - IVA aplicable (10%, 5%, Exenta)
10. **porc_iva_display** (calculated) - Porcentaje IVA mostrado automáticamente

### Estado y Auditoría

11. **activo** (boolean, default: true) - Estado del artículo
12. **usuario_alta** (auto) - Usuario que creó el registro
13. **fec_alta** (auto) - Fecha de creación
14. **usuario_mod** (auto) - Usuario que modificó
15. **fec_mod** (auto) - Fecha de modificación

## Funcionalidad Removida

-   ❌ Carga de imágenes con Spatie Media Library
-   ❌ FileUpload component
-   ❌ ImageColumn en tabla
-   ❌ Sección completa "Imagen del Artículo"

## Migraciones Ejecutadas

```bash
php artisan migrate
# 2025_11_11_203509_create_impuestos_table .......... DONE
# 2025_11_11_203541_add_cod_impuesto_to_articulos_table .. DONE
```

## Validaciones y Características

### Validaciones del Formulario

-   Descripción: requerida, max 255 caracteres
-   Marca: requerida, searchable, con createOptionForm
-   Modelo: requerido, searchable, con createOptionForm
-   Tipo de Artículo: requerido, searchable
-   Costo: requerido, numérico, min 0, step 1000
-   Precio: requerido, numérico, min 0, step 1000
-   IVA: requerido, default IVA 10%

### Características de la Tabla

-   Ordenamiento por defecto: fec_alta DESC
-   Persistencia de: ordenamiento, búsqueda, filtros
-   Summarizers: promedio de costo y precio
-   Badge condicional en margen:
    -   < 20% → danger (rojo)
    -   < 40% → warning (amarillo)
    -   > = 40% → success (verde)
-   Filtros: Estado (default: Activo), Marca, Tipo, IVA, Rango de Precios

### Bulk Actions

-   Eliminar seleccionados
-   Activar seleccionados
-   Desactivar seleccionados

## Patrón de Diseño Aplicado

-   ✅ Sections con iconos y descripciones
-   ✅ Grid layouts responsivos
-   ✅ Campos live() para cálculos reactivos
-   ✅ Placeholders para valores calculados
-   ✅ Botones personalizados con keyboard shortcuts
-   ✅ Sin emojis (consistente con PersonasResource)
-   ✅ Badges con colores semánticos
-   ✅ IconColumn en lugar de BadgeColumn para estado

## Notas Importantes

1. El porcentaje de IVA se obtiene automáticamente de la tabla `impuestos` al seleccionar el tipo
2. Los datos de IVA vienen pre-cargados (10%, 5%, Exenta) en la migración
3. El margen de ganancia se calcula en tiempo real: `((precio - costo) / costo) * 100`
4. La relación con impuestos usa `onDelete('set null')` para mantener integridad referencial
5. Los campos de auditoría se llenan automáticamente en Create y Edit

## Estado Final

✅ Formulario completo para registro de repuestos
✅ Campo IVA (porc_iva) integrado desde tabla impuestos
✅ Funcionalidad de imágenes completamente removida
✅ UI/UX consistente con otros recursos (Personas, Proveedores, CompraCabecera)
✅ Custom buttons con keyboard shortcuts
✅ Sin errores de compilación
