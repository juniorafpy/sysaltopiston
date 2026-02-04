# Migraciones del Sistema de Compras

## Resumen de cambios realizados

### 1. Migración creada: `cm_compras_detalle`

**Archivo:** `2025_11_10_232550_create_cm_compras_detalle_table.php`

Tabla de detalles de compras con:

-   `id_compra_detalle` (PK)
-   `id_compra_cabecera` (FK a cm_compras_cabecera)
-   `cod_articulo` (FK a articulos)
-   `cantidad` (decimal 15,2)
-   `precio_unitario` (decimal 15,2)
-   `porcentaje_iva` (decimal 5,2, default 10.00)
-   `monto_total_linea` (decimal 15,2)
-   Índices en id_compra_cabecera y cod_articulo
-   Cascade delete con cabecera
-   Restrict delete con artículo

### 2. Migración creada: Campos de auditoría

**Archivo:** `2025_11_10_232621_add_audit_fields_to_compras_tables.php`

Agregado a `cm_compras_cabecera`:

-   `usuario_alta` (string 100, nullable)
-   `fecha_alta` (timestamp, nullable)
-   `usuario_mod` (string 100, nullable)
-   `fecha_mod` (timestamp, nullable)
-   `created_at` y `updated_at` (timestamps estándar Laravel)

### 3. Modelos actualizados

#### CompraCabecera

-   Agregados campos fillable para auditoría
-   Agregados casts para fechas
-   Implementado `boot()` con:
    -   `creating`: auto-asigna usuario_alta y fecha_alta
    -   `updating`: auto-asigna usuario_mod y fecha_mod
-   Nuevas relaciones:
    -   `sucursal()`
    -   `usuarioAlta()`
    -   `usuarioMod()`
-   Accessors:
    -   `numero_completo`: formato "TIP-SER-NRO"
    -   `total_compra`: suma de monto_total_linea de detalles

#### CompraDetalle

-   Agregados casts para campos decimales
-   Accessors:
    -   `monto_iva`: calcula IVA del total
    -   `monto_sin_iva`: total menos IVA
-   Método `calcularTotalLinea()`: cantidad × precio_unitario

## Estado actual

✅ Tabla `cm_compras_cabecera`: 18 columnas
✅ Tabla `cm_compras_detalle`: 7 columnas
✅ Modelos `CompraCabecera` y `CompraDetalle` actualizados
✅ Relaciones configuradas correctamente
✅ Campos de auditoría funcionando

## Migraciones ejecutadas

```bash
php artisan migrate
```

Resultado:

-   ✅ 2025_11_10_232550_create_cm_compras_detalle_table
-   ✅ 2025_11_10_232621_add_audit_fields_to_compras_tables

## Script de prueba

Archivo: `test_compras.php`

Ejecutar con:

```bash
php test_compras.php
```

Verifica:

-   Estructura de tablas
-   Relaciones de modelos
-   Datos existentes
-   Última compra (si existe)

## Próximos pasos sugeridos

1. **Crear Filament Resource para Compras**

    - CompraCabeceraResource con form y table
    - Incluir Repeater para detalles
    - Validaciones de stock y precios

2. **Seeders de prueba**

    - Proveedores de ejemplo
    - Compras de prueba con detalles

3. **Integración con stock**

    - Actualizar `existe_stock` al crear compra
    - Trigger o observer para movimientos

4. **Reportes**
    - Listado de compras por proveedor
    - Estadísticas de compras por período
    - Análisis de costos

## Comandos útiles

```bash
# Ver estado de migraciones
php artisan migrate:status

# Rollback última migración
php artisan migrate:rollback

# Fresh (borra todo y vuelve a migrar - CUIDADO!)
php artisan migrate:fresh --seed

# Ver estructura de tabla
php artisan db:table cm_compras_cabecera
php artisan db:table cm_compras_detalle
```

## Notas importantes

-   Las compras ahora tienen auditoría completa (quién y cuándo)
-   Los timestamps están habilitados en cabecera pero NO en detalle
-   El IVA por defecto es 10% (configurable por línea)
-   Las FKs tienen protección: cascade en detalles, restrict en artículos
-   Los campos usuario_alta/mod almacenan el ID del usuario (Auth::id())
