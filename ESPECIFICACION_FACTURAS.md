# ESPECIFICACIÓN TÉCNICA: Módulo de Facturas

## 1. Introducción

El módulo de Facturas permite generar comprobantes de venta con dos flujos diferentes: **desde Presupuestos aprobados** o **facturación directa** sin presupuesto previo. Al generar una factura, el sistema automáticamente:

1. Registra la operación en el **Libro IVA** (separando IVA 5%, 10% y Exentas) para cumplimiento tributario SET.
2. Crea un **saldo en Cuenta Corriente** si la condición de venta es a crédito.
3. Registra un **ingreso en Caja** si la condición de venta es contado y hay una caja abierta.
4. Incrementa el **número actual del timbrado** utilizado.
5. Actualiza el **estado del Presupuesto** a "Facturado" si corresponde.

## 2. Estructura de Base de Datos

El módulo utiliza 5 tablas principales:

### 2.1 Tabla: timbrados

Almacena los timbrados autorizados por la SET (Subsecretaría de Estado de Tributación) para la emisión de facturas.

```sql
CREATE TABLE timbrados (
    cod_timbrado BIGSERIAL PRIMARY KEY,
    numero_timbrado VARCHAR(20) UNIQUE NOT NULL,
    fecha_inicio_vigencia DATE NOT NULL,
    fecha_fin_vigencia DATE NOT NULL,
    numero_inicial VARCHAR(20) NOT NULL,
    numero_final VARCHAR(20) NOT NULL,
    numero_actual VARCHAR(20) NOT NULL,
    establecimiento VARCHAR(3) DEFAULT '001',
    punto_expedicion VARCHAR(3) DEFAULT '001',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos clave:**

-   `numero_timbrado`: Número del timbrado SET (8 dígitos).
-   `fecha_inicio_vigencia` / `fecha_fin_vigencia`: Rango de vigencia autorizado.
-   `numero_inicial` / `numero_final`: Rango de números de factura autorizados.
-   `numero_actual`: Próximo número de factura disponible (se auto-incrementa).
-   `establecimiento` / `punto_expedicion`: Identificación del punto de venta (001-001, 001-002, etc.).

### 2.2 Tabla: facturas

Almacena el encabezado de cada factura emitida.

```sql
CREATE TABLE facturas (
    cod_factura BIGSERIAL PRIMARY KEY,
    cod_timbrado BIGINT REFERENCES timbrados(cod_timbrado),
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    fecha_factura DATE NOT NULL,
    cod_cliente BIGINT REFERENCES personas(cod_persona),
    condicion_venta ENUM('Contado', 'Crédito') DEFAULT 'Contado',
    presupuesto_venta_id BIGINT NULLABLE,
    orden_servicio_id BIGINT NULLABLE,
    subtotal_gravado_10 DECIMAL(15,2) DEFAULT 0,
    subtotal_gravado_5 DECIMAL(15,2) DEFAULT 0,
    subtotal_exenta DECIMAL(15,2) DEFAULT 0,
    total_iva_10 DECIMAL(15,2) DEFAULT 0,
    total_iva_5 DECIMAL(15,2) DEFAULT 0,
    total_general DECIMAL(15,2) DEFAULT 0,
    estado ENUM('Emitida', 'Anulada', 'Pagada') DEFAULT 'Emitida',
    observaciones TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos clave:**

-   `numero_factura`: Formato 001-001-0000123 (establecimiento-punto_expedicion-numero).
-   `condicion_venta`: Determina si crea saldo en CC (Crédito) o ingreso en caja (Contado).
-   `presupuesto_venta_id`: NULLABLE permite facturas directas sin presupuesto.
-   `subtotal_gravado_10/5`, `total_iva_10/5`, `subtotal_exenta`: Separación por tipo de IVA para Libro IVA.

### 2.3 Tabla: factura_detalles

Almacena los ítems de cada factura.

```sql
CREATE TABLE factura_detalles (
    cod_detalle BIGSERIAL PRIMARY KEY,
    cod_factura BIGINT REFERENCES facturas(cod_factura) ON DELETE CASCADE,
    cod_articulo BIGINT REFERENCES articulos(cod_articulo) ON DELETE RESTRICT,
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    porcentaje_descuento DECIMAL(5,2) DEFAULT 0,
    monto_descuento DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    tipo_iva ENUM('10', '5', 'Exenta') DEFAULT '10',
    porcentaje_iva DECIMAL(5,2) DEFAULT 0,
    monto_iva DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos clave:**

-   `tipo_iva`: Determina el cálculo del IVA (10%, 5% o Exenta).
-   `monto_iva`: El IVA ya incluido en el precio (Paraguay maneja IVA incluido).
-   `subtotal` = (cantidad \* precio_unitario) - monto_descuento.
-   `total` = `subtotal` (el precio ya incluye IVA).

### 2.4 Tabla: libro_iva

Registro de todas las operaciones de compra y venta con IVA separado por tasa (5%, 10%, Exenta) para el reporte mensual a la SET.

```sql
CREATE TABLE libro_iva (
    cod_registro BIGSERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    timbrado VARCHAR(20) NOT NULL,
    numero_factura VARCHAR(20) NOT NULL,
    ruc_cliente VARCHAR(20),
    razon_social VARCHAR(255),
    gravado_10 DECIMAL(15,2) DEFAULT 0,
    iva_10 DECIMAL(15,2) DEFAULT 0,
    gravado_5 DECIMAL(15,2) DEFAULT 0,
    iva_5 DECIMAL(15,2) DEFAULT 0,
    exentas DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    tipo_operacion ENUM('Venta', 'Compra') DEFAULT 'Venta',
    cod_factura BIGINT REFERENCES facturas(cod_factura) ON DELETE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Propósito:**

-   Libro IVA Ventas para declaración jurada mensual a la SET.
-   Separación por tasa (gravado_10, iva_10, gravado_5, iva_5, exentas).
-   Un registro por cada factura emitida.

### 2.5 Tabla: cc_saldos

Registro de movimientos de cuenta corriente de clientes (debe y haber).

```sql
CREATE TABLE cc_saldos (
    cod_saldo BIGSERIAL PRIMARY KEY,
    cod_cliente BIGINT REFERENCES personas(cod_persona) ON DELETE RESTRICT,
    tipo_comprobante VARCHAR(50) NOT NULL,
    nro_comprobante VARCHAR(20) NOT NULL,
    fecha_comprobante DATE NOT NULL,
    debe DECIMAL(15,2) DEFAULT 0,
    haber DECIMAL(15,2) DEFAULT 0,
    saldo_actual DECIMAL(15,2) NOT NULL,
    descripcion TEXT NULLABLE,
    cod_factura BIGINT REFERENCES facturas(cod_factura) ON DELETE CASCADE,
    usuario_alta BIGINT NULLABLE,
    fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos clave:**

-   `debe`: Monto que el cliente debe (facturas a crédito).
-   `haber`: Monto que el cliente paga (recibos).
-   `saldo_actual`: Saldo acumulado después de este movimiento.
-   `tipo_comprobante`: 'Factura', 'Recibo', 'Nota Crédito', 'Nota Débito'.

## 3. Modelos de Laravel

### 3.1 Modelo: Timbrado

**Ubicación:** `app/Models/Timbrado.php`

**Métodos principales:**

```php
// Verifica si el timbrado está vigente en una fecha
public function estaVigente(?Carbon $fecha = null): bool

// Obtiene el siguiente número de factura disponible
public function obtenerSiguienteNumero(): string

// Incrementa el número actual después de generar una factura
public function incrementarNumeroActual(): void

// Formatea el número de factura completo (001-001-0000123)
public function formatearNumeroFactura(string $numeroFactura): string
```

**Scopes:**

```php
// Timbrados activos
Timbrado::activos()->get()

// Timbrados vigentes (activos + dentro del rango de fecha + con números disponibles)
Timbrado::vigentes()->get()
```

**Accessors:**

```php
$timbrado->estado_vigencia  // 'Vigente', 'Vencido', 'Agotado', 'Pendiente', 'Inactivo'
$timbrado->numeros_disponibles  // Cantidad de facturas disponibles
```

### 3.2 Modelo: Factura

**Ubicación:** `app/Models/Factura.php`

**Métodos principales:**

```php
// Calcula los totales de la factura desde los detalles
public function calcularTotales(): array

// Inserta el registro en el libro IVA
public function insertarLibroIva(): void

// Inserta el saldo en cuenta corriente (solo para crédito)
public function insertarCCSaldo(): void

// Inserta movimiento de caja (solo para contado)
public function insertarMovimientoCaja(): void

// Proceso completo de generación de factura con transacción
public static function generarFactura(array $data): self
```

**Proceso del método `generarFactura()`:**

1. Valida que el timbrado esté vigente.
2. Obtiene el siguiente número de factura del timbrado.
3. Crea el registro de la factura.
4. Crea los detalles de la factura.
5. Recalcula totales desde detalles (para asegurar consistencia).
6. Inserta registro en libro_iva.
7. Si es crédito: Inserta saldo en cc_saldos.
8. Si es contado: Inserta movimiento en caja (si hay caja abierta).
9. Incrementa el número_actual del timbrado.
10. Si viene de presupuesto: Actualiza estado del presupuesto a 'Facturado'.

**Ejemplo de uso:**

```php
$factura = Factura::generarFactura([
    'cod_timbrado' => 1,
    'fecha_factura' => '2025-11-10',
    'cod_cliente' => 5,
    'condicion_venta' => 'Crédito',
    'presupuesto_venta_id' => 10, // NULLABLE para facturas directas
    'subtotal_gravado_10' => 1000000,
    'subtotal_gravado_5' => 0,
    'subtotal_exenta' => 0,
    'total_iva_10' => 90909,
    'total_iva_5' => 0,
    'total_general' => 1000000,
    'detalles' => [
        [
            'cod_articulo' => 3,
            'descripcion' => 'Aceite Sintético',
            'cantidad' => 2,
            'precio_unitario' => 500000,
            'porcentaje_descuento' => 0,
            'monto_descuento' => 0,
            'subtotal' => 1000000,
            'tipo_iva' => '10',
            'porcentaje_iva' => 10,
            'monto_iva' => 90909,
            'total' => 1000000,
        ]
    ],
]);
```

### 3.3 Modelo: FacturaDetalle

**Ubicación:** `app/Models/FacturaDetalle.php`

**Métodos estáticos para cálculos:**

```php
// Calcula el subtotal (cantidad * precio_unitario - descuento)
public static function calcularSubtotal(float $cantidad, float $precioUnitario, float $porcentajeDescuento = 0): float

// Calcula el monto de descuento
public static function calcularMontoDescuento(float $cantidad, float $precioUnitario, float $porcentajeDescuento): float

// Calcula el monto de IVA según el tipo (IVA incluido en precio)
public static function calcularMontoIva(float $subtotal, string $tipoIva): float

// Obtiene el porcentaje de IVA según el tipo
public static function obtenerPorcentajeIva(string $tipoIva): float

// Calcula todos los valores de un detalle
public static function calcularDetalle(array $data): array
```

**Cálculo de IVA (Paraguay usa IVA incluido):**

```php
// Para IVA 10%:
$montoIva = ($subtotal * 10) / 110;

// Para IVA 5%:
$montoIva = ($subtotal * 5) / 105;

// Exenta:
$montoIva = 0;
```

### 3.4 Modelo: LibroIva

**Ubicación:** `app/Models/LibroIva.php`

**Scopes:**

```php
// Filtrar ventas
LibroIva::ventas()->get()

// Filtrar compras
LibroIva::compras()->get()

// Filtrar por rango de fechas
LibroIva::entreFechas('2025-11-01', '2025-11-30')->get()

// Filtrar por mes y año
LibroIva::delMes(11, 2025)->get()
```

**Accessors:**

```php
$registro->total_iva  // Suma de iva_10 + iva_5
$registro->total_gravado  // Suma de gravado_10 + gravado_5 + exentas
```

### 3.5 Modelo: CCSaldo

**Ubicación:** `app/Models/CCSaldo.php`

**Métodos estáticos:**

```php
// Obtiene el saldo actual de un cliente
public static function obtenerSaldoCliente(int $codCliente): float

// Registra un pago (haber)
public static function registrarPago(int $codCliente, float $monto, string $nroComprobante, string $descripcion = null): self
```

**Scopes:**

```php
// Movimientos de un cliente
CCSaldo::deCliente($codCliente)->get()

// Movimientos con saldo pendiente
CCSaldo::conSaldoPendiente()->get()

// Débitos (facturas)
CCSaldo::debitos()->get()

// Créditos (pagos)
CCSaldo::creditos()->get()
```

**Accessors:**

```php
$saldo->tipo_movimiento  // 'Débito', 'Crédito', 'Sin movimiento'
$saldo->monto  // Retorna debe o haber según corresponda
```

## 4. Resource de Filament

### 4.1 FacturaResource

**Ubicación:** `app/Filament/Resources/FacturaResource.php`

**Formulario dinámico con 4 secciones:**

#### Sección 1: Tipo de Factura (solo en create)

-   **Toggle "Generar desde Presupuesto":**
    -   Si está activado: Muestra select de presupuestos aprobados.
    -   Si está desactivado: Permite facturación directa.

#### Sección 2: Información de la Factura

-   **Si desde presupuesto:**

    -   Select de presupuestos aprobados (con cliente y total).
    -   Al seleccionar: Auto-carga cliente, fecha y detalles del presupuesto.
    -   Campos deshabilitados: cliente, detalles.

-   **Si factura directa:**
    -   Select de timbrado vigente (muestra números disponibles).
    -   Fecha de factura (default hoy).
    -   Select de cliente (es_cliente = true).
    -   Select de condición de venta (Contado/Crédito).
    -   Observaciones.

#### Sección 3: Detalles de la Factura

-   **Repeater de detalles** con cálculos automáticos live:

    -   Select de artículo (activos).
    -   Descripción (auto-carga desde artículo).
    -   Cantidad (default 1).
    -   Precio unitario (auto-carga desde artículo).
    -   Porcentaje de descuento (0-100%).
    -   Select de tipo IVA (10%, 5%, Exenta).
    -   Campos calculados automáticamente:
        -   Monto descuento
        -   Subtotal
        -   Porcentaje IVA
        -   Monto IVA
        -   Total

-   **Cálculos automáticos:**

```php
// Al cambiar cantidad, precio_unitario, porcentaje_descuento o tipo_iva:
protected static function calcularDetalle(Set $set, Get $get): void
{
    $cantidad = floatval($get('cantidad') ?? 0);
    $precioUnitario = floatval($get('precio_unitario') ?? 0);
    $porcentajeDescuento = floatval($get('porcentaje_descuento') ?? 0);
    $tipoIva = $get('tipo_iva') ?? '10';

    // Calcular descuento
    $importeBruto = $cantidad * $precioUnitario;
    $montoDescuento = ($importeBruto * $porcentajeDescuento) / 100;
    $subtotal = $importeBruto - $montoDescuento;

    // Calcular IVA (incluido)
    $montoIva = match ($tipoIva) {
        '10' => ($subtotal * 10) / 110,
        '5' => ($subtotal * 5) / 105,
        default => 0,
    };

    $set('monto_descuento', round($montoDescuento, 2));
    $set('subtotal', round($subtotal, 2));
    $set('monto_iva', round($montoIva, 2));
    $set('total', round($subtotal, 2));
}
```

#### Sección 4: Totales

-   **Totales calculados automáticamente desde detalles:**

    -   Gravado 10% + IVA 10%
    -   Gravado 5% + IVA 5%
    -   Exentas
    -   **TOTAL GENERAL** (destacado)

-   **Cálculo automático:**

```php
protected static function calcularTotalesFactura(Set $set, Get $get): void
{
    $detalles = collect($get('detalles') ?? []);

    $subtotalGravado10 = 0;
    $totalIva10 = 0;
    $subtotalGravado5 = 0;
    $totalIva5 = 0;
    $subtotalExenta = 0;

    foreach ($detalles as $detalle) {
        $tipoIva = $detalle['tipo_iva'] ?? '10';
        $subtotal = floatval($detalle['subtotal'] ?? 0);
        $montoIva = floatval($detalle['monto_iva'] ?? 0);

        if ($tipoIva === '10') {
            $subtotalGravado10 += $subtotal;
            $totalIva10 += $montoIva;
        } elseif ($tipoIva === '5') {
            $subtotalGravado5 += $subtotal;
            $totalIva5 += $montoIva;
        } elseif ($tipoIva === 'Exenta') {
            $subtotalExenta += $subtotal;
        }
    }

    $totalGeneral = $subtotalGravado10 + $subtotalGravado5 + $subtotalExenta;

    $set('subtotal_gravado_10', round($subtotalGravado10, 2));
    $set('total_iva_10', round($totalIva10, 2));
    $set('subtotal_gravado_5', round($subtotalGravado5, 2));
    $set('total_iva_5', round($totalIva5, 2));
    $set('subtotal_exenta', round($subtotalExenta, 2));
    $set('total_general', round($totalGeneral, 2));
}
```

### 4.2 Tabla de Facturas

**Columnas:**

-   Número de factura (searchable, sortable)
-   Fecha (formato d/m/Y)
-   Cliente (nombre completo)
-   Condición de venta (badge: Contado=success, Crédito=warning)
-   Total (formato moneda PYG)
-   Estado (badge: Emitida=success, Anulada=danger, Pagada=info)
-   Fecha de creación (oculta por default, toggleable)

**Filtros:**

-   Por condición de venta (Contado/Crédito)
-   Por estado (Emitida/Anulada/Pagada)

**Orden:**

-   Por defecto: fecha_factura DESC

## 5. Páginas de Filament

### 5.1 ListFacturas

**Ubicación:** `app/Filament/Resources/FacturaResource/Pages/ListFacturas.php`

-   Lista paginada de facturas.
-   Botón "Nueva Factura" en el header.

### 5.2 CreateFactura

**Ubicación:** `app/Filament/Resources/FacturaResource/Pages/CreateFactura.php`

**Proceso:**

1. **mutateFormDataBeforeCreate():**

    - Remueve campo virtual 'desde_presupuesto'.
    - Asegura estado='Emitida'.

2. **handleRecordCreation():**

    - Llama a `Factura::generarFactura($data)`.
    - Genera 3 notificaciones:
        - "Factura generada exitosamente" (success) con número y total.
        - "Saldo registrado en Cuenta Corriente" (info) si es crédito.
        - "Ingreso registrado en Caja" (info) si es contado.
        - "Registro en Libro IVA" (info) siempre.

3. **Redirección:**
    - Redirige a la página de visualización de la factura creada.

### 5.3 ViewFactura

**Ubicación:** `app/Filament/Resources/FacturaResource/Pages/ViewFactura.php`

**Infolist con 6 secciones:**

1. **Información de la Factura:**

    - Número, Timbrado, Fecha, Cliente, RUC, Condición de venta, Estado, Observaciones.

2. **Detalles:**

    - RepeatableEntry con 8 columnas: Descripción, Cantidad, Precio Unit., % Desc., IVA, Subtotal, IVA, Total.

3. **Totales:**

    - Gravado 10%, IVA 10%, Gravado 5%, IVA 5%, Exentas, TOTAL GENERAL (destacado).

4. **Información Relacionada (collapsible):**

    - Presupuesto Nro. (con link si existe).
    - Orden de Servicio Nro. (con link si existe).

5. **Auditoría (collapsible, collapsed):**
    - Fecha de creación, Última modificación.

**Acciones en header:**

-   Botón "Editar" (solo si estado=Emitida).

### 5.4 EditFactura

**Ubicación:** `app/Filament/Resources/FacturaResource/Pages/EditFactura.php`

-   Permite editar observaciones y estado.
-   Botón "Eliminar" (solo si estado=Emitida).
-   Los campos de timbrado, cliente, detalles están deshabilitados (no se pueden modificar después de creada).

## 6. Seeders

### 6.1 TimbradoSeeder

**Ubicación:** `database/seeders/TimbradoSeeder.php`

**Crea 5 timbrados:**

1. **Timbrado vigente actual** (001-001):

    - Vigencia: Hoy -1 mes hasta +5 meses.
    - Rango: 0000001 - 0001000.

2. **Timbrado vigente punto 002** (001-002):

    - Vigencia: Hoy -2 meses hasta +4 meses.
    - Rango: 0000001 - 0000500.

3. **Timbrado próximo a vencer** (002-001):

    - Vigencia: Hoy -6 meses hasta +15 días.
    - Rango: 0000001 - 0000200, número_actual=0000180 (casi agotado).

4. **Timbrado vencido** (001-001):

    - Vigencia: Hoy -12 meses hasta -1 mes (ya venció).
    - Activo=false.

5. **Timbrado futuro** (001-001):
    - Vigencia: Hoy +6 meses hasta +12 meses (aún no inicia).

### 6.2 FacturaSeeder

**Ubicación:** `database/seeders/FacturaSeeder.php`

**Crea hasta 20 facturas:**

-   **5 desde presupuestos aprobados** (si existen):

    -   Carga detalles del presupuesto.
    -   50% Contado, 50% Crédito.

-   **15 facturas directas:**
    -   Cliente aleatorio.
    -   1-5 items por factura.
    -   Artículos aleatorios.
    -   Descuentos 0-20%.
    -   Tipo IVA aleatorio (10%, 5%, Exenta).
    -   66% Contado, 33% Crédito.

**Muestra resumen:**

-   Total de facturas.
-   Cantidad Contado vs Crédito.
-   Cantidad desde presupuesto vs directas.
-   Registros en libro_iva.
-   Registros en cc_saldos.

### 6.3 DatabaseSeeder

**Ubicación:** `database/seeders/DatabaseSeeder.php`

**Actualizado con:**

```php
$this->call([
    // ... seeders existentes
    CajaSeeder::class,
    AperturaCajaSeeder::class,
    TimbradoSeeder::class,
    FacturaSeeder::class,
]);
```

## 7. Flujos de Trabajo

### 7.1 Flujo: Factura desde Presupuesto

**Pasos del usuario:**

1. Usuario navega a "Facturas" → "Nueva Factura".
2. Activa el toggle "Generar desde Presupuesto".
3. Selecciona un presupuesto aprobado del dropdown.
4. El sistema auto-carga:
    - Cliente del presupuesto.
    - Fecha actual.
    - Detalles del presupuesto con cálculos.
5. Usuario selecciona:
    - Timbrado vigente.
    - Condición de venta (Contado/Crédito).
    - Observaciones (opcional).
6. Usuario revisa totales calculados automáticamente.
7. Click en "Guardar".

**Proceso del sistema:**

1. Valida que el timbrado esté vigente.
2. Obtiene siguiente número de factura (ej: 001-001-0000123).
3. Crea factura con estado='Emitida'.
4. Crea detalles de factura.
5. Recalcula totales para asegurar consistencia.
6. **Inserta en libro_iva:**

    ```php
    LibroIva::create([
        'fecha' => '2025-11-10',
        'timbrado' => '12345678',
        'numero_factura' => '001-001-0000123',
        'ruc_cliente' => '80012345-7',
        'razon_social' => 'Juan Pérez',
        'gravado_10' => 909091,  // subtotal sin IVA
        'iva_10' => 90909,       // IVA 10%
        'gravado_5' => 0,
        'iva_5' => 0,
        'exentas' => 0,
        'total' => 1000000,
        'tipo_operacion' => 'Venta',
        'cod_factura' => 123,
    ]);
    ```

7. **Si condicion_venta='Crédito', inserta en cc_saldos:**

    ```php
    CCSaldo::create([
        'cod_cliente' => 5,
        'tipo_comprobante' => 'Factura',
        'nro_comprobante' => '001-001-0000123',
        'fecha_comprobante' => '2025-11-10',
        'debe' => 1000000,  // Cliente debe este monto
        'haber' => 0,
        'saldo_actual' => 1000000,  // Saldo anterior + debe
        'descripcion' => 'Factura Nro: 001-001-0000123 - Total: Gs. 1.000.000',
        'cod_factura' => 123,
    ]);
    ```

8. **Si condicion_venta='Contado' y hay caja abierta, inserta en movimientos_caja:**

    ```php
    MovimientoCaja::create([
        'cod_apertura_caja' => 10,
        'tipo_movimiento' => 'Ingreso',
        'concepto' => 'Factura Nro: 001-001-0000123',
        'monto' => 1000000,
        'tipo_documento' => 'Factura',
        'documento_id' => 123,
    ]);
    ```

9. Incrementa timbrado:

    ```php
    $timbrado->numero_actual = '0000124';
    $timbrado->save();
    ```

10. Actualiza presupuesto:

    ```php
    $presupuesto->estado = 'Facturado';
    $presupuesto->save();
    ```

11. Muestra 3 notificaciones de éxito.
12. Redirige a la vista de la factura.

### 7.2 Flujo: Factura Directa (sin presupuesto)

**Pasos del usuario:**

1. Usuario navega a "Facturas" → "Nueva Factura".
2. Deja desactivado el toggle "Generar desde Presupuesto".
3. Selecciona:
    - Timbrado vigente.
    - Fecha de factura.
    - Cliente.
    - Condición de venta (Contado/Crédito).
4. Agrega items en el repeater:
    - Selecciona artículo (auto-carga descripción y precio).
    - Ajusta cantidad.
    - Ajusta descuento (si aplica).
    - Selecciona tipo de IVA (10%, 5%, Exenta).
    - Revisa cálculos automáticos (subtotal, IVA, total).
5. Revisa totales generales calculados automáticamente.
6. Agrega observaciones (opcional).
7. Click en "Guardar".

**Proceso del sistema:**

-   Mismo que el flujo anterior, pero:
    -   `presupuesto_venta_id` = NULL.
    -   No actualiza estado de presupuesto.
    -   Detalles provienen del repeater manual.

### 7.3 Flujo: Consulta de Libro IVA

**Ejemplo SQL para reporte mensual:**

```sql
SELECT
    fecha,
    timbrado,
    numero_factura,
    ruc_cliente,
    razon_social,
    gravado_10,
    iva_10,
    gravado_5,
    iva_5,
    exentas,
    total
FROM libro_iva
WHERE tipo_operacion = 'Venta'
  AND EXTRACT(MONTH FROM fecha) = 11
  AND EXTRACT(YEAR FROM fecha) = 2025
ORDER BY fecha, numero_factura;
```

**Totales para declaración jurada:**

```sql
SELECT
    SUM(gravado_10) as total_gravado_10,
    SUM(iva_10) as total_iva_10,
    SUM(gravado_5) as total_gravado_5,
    SUM(iva_5) as total_iva_5,
    SUM(exentas) as total_exentas,
    SUM(total) as total_general
FROM libro_iva
WHERE tipo_operacion = 'Venta'
  AND EXTRACT(MONTH FROM fecha) = 11
  AND EXTRACT(YEAR FROM fecha) = 2025;
```

### 7.4 Flujo: Consulta de Cuenta Corriente de Cliente

**Ejemplo SQL para estado de cuenta:**

```sql
SELECT
    fecha_comprobante,
    tipo_comprobante,
    nro_comprobante,
    descripcion,
    debe,
    haber,
    saldo_actual
FROM cc_saldos
WHERE cod_cliente = 5
ORDER BY fecha_comprobante, cod_saldo;
```

**Resultado ejemplo:**

| Fecha | Tipo    | Nro             | Descripción  | Debe      | Haber     | Saldo     |
| ----- | ------- | --------------- | ------------ | --------- | --------- | --------- |
| 01/11 | Factura | 001-001-0000123 | Factura ...  | 1.000.000 | 0         | 1.000.000 |
| 05/11 | Recibo  | REC-001         | Pago parcial | 0         | 500.000   | 500.000   |
| 10/11 | Factura | 001-001-0000145 | Factura ...  | 800.000   | 0         | 1.300.000 |
| 15/11 | Recibo  | REC-002         | Pago total   | 0         | 1.300.000 | 0         |

**Saldo actual del cliente:**

```php
$saldoActual = CCSaldo::obtenerSaldoCliente(5);
```

### 7.5 Flujo: Registro de Pago (Recibo)

**Ejemplo de código para registrar un pago:**

```php
CCSaldo::registrarPago(
    codCliente: 5,
    monto: 500000,
    nroComprobante: 'REC-001',
    descripcion: 'Pago parcial de factura 001-001-0000123'
);
```

**Resultado en cc_saldos:**

```php
[
    'cod_cliente' => 5,
    'tipo_comprobante' => 'Recibo',
    'nro_comprobante' => 'REC-001',
    'fecha_comprobante' => '2025-11-10',
    'debe' => 0,
    'haber' => 500000,
    'saldo_actual' => 500000,  // 1.000.000 - 500.000
    'descripcion' => 'Pago parcial de factura 001-001-0000123',
]
```

## 8. Validaciones y Reglas de Negocio

### 8.1 Validaciones en el Timbrado

-   **Vigencia:** Solo se pueden usar timbrados con `fecha_inicio_vigencia <= HOY <= fecha_fin_vigencia`.
-   **Números disponibles:** `numero_actual <= numero_final`.
-   **Activo:** `activo = true`.

**Query para timbrados vigentes:**

```php
Timbrado::where('activo', true)
    ->where('fecha_inicio_vigencia', '<=', now())
    ->where('fecha_fin_vigencia', '>=', now())
    ->whereRaw("CAST(numero_actual AS INTEGER) <= CAST(numero_final AS INTEGER)")
    ->get();
```

### 8.2 Validaciones en la Factura

-   **Cliente es requerido.**
-   **Timbrado debe estar vigente** al momento de generar la factura.
-   **Número de factura es único** (001-001-0000123).
-   **Detalles:** Al menos 1 item.
-   **Total general > 0.**

### 8.3 Validaciones en los Detalles

-   **Cantidad > 0.**
-   **Precio unitario >= 0.**
-   **Porcentaje descuento entre 0 y 100.**
-   **Tipo IVA:** '10', '5' o 'Exenta'.

### 8.4 Reglas de Negocio

1. **Una factura solo puede tener un timbrado.**
2. **El número de factura se genera automáticamente** desde el timbrado (no se puede ingresar manualmente).
3. **Las facturas a crédito generan saldos en cc_saldos** (debe).
4. **Las facturas a contado generan ingresos en movimientos_caja** (si hay caja abierta).
5. **Todas las facturas se registran en libro_iva** con IVA separado por tasa.
6. **Los presupuestos solo pueden facturarse una vez** (relación 1:1).
7. **Las facturas no se pueden eliminar** después de emitidas (se anulan cambiando estado).
8. **El IVA es incluido** en los precios (Paraguay).

## 9. Reportes Sugeridos

### 9.1 Reporte: Libro IVA Ventas Mensual

**Consulta:**

```sql
SELECT
    fecha,
    timbrado,
    numero_factura,
    ruc_cliente,
    razon_social,
    gravado_10,
    iva_10,
    gravado_5,
    iva_5,
    exentas,
    total
FROM libro_iva
WHERE tipo_operacion = 'Venta'
  AND fecha BETWEEN '2025-11-01' AND '2025-11-30'
ORDER BY fecha, numero_factura;
```

**Totales:**

```sql
SELECT
    COUNT(*) as cantidad_facturas,
    SUM(gravado_10) as total_gravado_10,
    SUM(iva_10) as total_iva_10,
    SUM(gravado_5) as total_gravado_5,
    SUM(iva_5) as total_iva_5,
    SUM(exentas) as total_exentas,
    SUM(total) as total_general
FROM libro_iva
WHERE tipo_operacion = 'Venta'
  AND fecha BETWEEN '2025-11-01' AND '2025-11-30';
```

### 9.2 Reporte: Ventas por Condición de Venta

**Consulta:**

```sql
SELECT
    condicion_venta,
    COUNT(*) as cantidad_facturas,
    SUM(total_general) as total_ventas
FROM facturas
WHERE estado = 'Emitida'
  AND fecha_factura BETWEEN '2025-11-01' AND '2025-11-30'
GROUP BY condicion_venta;
```

### 9.3 Reporte: Cuenta Corriente por Cliente

**Consulta:**

```sql
SELECT
    c.nombre_completo as cliente,
    c.ruc,
    cc.fecha_comprobante,
    cc.tipo_comprobante,
    cc.nro_comprobante,
    cc.debe,
    cc.haber,
    cc.saldo_actual
FROM cc_saldos cc
JOIN personas c ON cc.cod_cliente = c.cod_persona
WHERE c.cod_persona = 5
ORDER BY cc.fecha_comprobante, cc.cod_saldo;
```

**Saldo pendiente:**

```sql
SELECT
    c.cod_persona,
    c.nombre_completo,
    c.ruc,
    COALESCE(
        (SELECT saldo_actual
         FROM cc_saldos
         WHERE cod_cliente = c.cod_persona
         ORDER BY fecha_comprobante DESC, cod_saldo DESC
         LIMIT 1),
        0
    ) as saldo_pendiente
FROM personas c
WHERE c.es_cliente = true
  AND COALESCE(
      (SELECT saldo_actual
       FROM cc_saldos
       WHERE cod_cliente = c.cod_persona
       ORDER BY fecha_comprobante DESC, cod_saldo DESC
       LIMIT 1),
      0
  ) > 0
ORDER BY saldo_pendiente DESC;
```

### 9.4 Reporte: Facturas por Vendedor/Cajero

**Consulta (si hay cod_usuario en facturas):**

```sql
SELECT
    u.name as vendedor,
    COUNT(*) as cantidad_facturas,
    SUM(f.total_general) as total_ventas
FROM facturas f
JOIN users u ON f.usuario_alta = u.id
WHERE f.estado = 'Emitida'
  AND f.fecha_factura BETWEEN '2025-11-01' AND '2025-11-30'
GROUP BY u.name
ORDER BY total_ventas DESC;
```

### 9.5 Reporte: Ventas por Artículo

**Consulta:**

```sql
SELECT
    a.descripcion as articulo,
    SUM(fd.cantidad) as cantidad_vendida,
    SUM(fd.subtotal) as total_subtotal,
    SUM(fd.total) as total_facturado
FROM factura_detalles fd
JOIN articulos a ON fd.cod_articulo = a.cod_articulo
JOIN facturas f ON fd.cod_factura = f.cod_factura
WHERE f.estado = 'Emitida'
  AND f.fecha_factura BETWEEN '2025-11-01' AND '2025-11-30'
GROUP BY a.descripcion
ORDER BY total_facturado DESC
LIMIT 20;
```

## 10. Comandos para Testing

### 10.1 Migrar y Sembrar

```bash
# Migrar tablas
php artisan migrate

# Ejecutar seeders completos
php artisan db:seed

# Ejecutar seeder específico
php artisan db:seed --class=TimbradoSeeder
php artisan db:seed --class=FacturaSeeder
```

### 10.2 Refrescar Base de Datos

```bash
# Refrescar con seeders
php artisan migrate:fresh --seed
```

### 10.3 Verificar Registros

```bash
# Consola tinker
php artisan tinker

# Verificar timbrados vigentes
>>> \App\Models\Timbrado::vigentes()->get()

# Verificar facturas
>>> \App\Models\Factura::count()

# Verificar libro IVA
>>> \App\Models\LibroIva::ventas()->count()

# Verificar saldos de cliente
>>> \App\Models\CCSaldo::obtenerSaldoCliente(5)
```

## 11. Archivos del Módulo

### Migraciones (5 archivos)

-   `database/migrations/2025_11_10_130001_create_timbrados_table.php`
-   `database/migrations/2025_11_10_130002_create_facturas_table.php`
-   `database/migrations/2025_11_10_130003_create_factura_detalles_table.php`
-   `database/migrations/2025_11_10_130004_create_libro_iva_table.php`
-   `database/migrations/2025_11_10_130005_create_cc_saldos_table.php`

### Modelos (5 archivos)

-   `app/Models/Timbrado.php`
-   `app/Models/Factura.php`
-   `app/Models/FacturaDetalle.php`
-   `app/Models/LibroIva.php`
-   `app/Models/CCSaldo.php`

### Resource y Páginas (5 archivos)

-   `app/Filament/Resources/FacturaResource.php`
-   `app/Filament/Resources/FacturaResource/Pages/ListFacturas.php`
-   `app/Filament/Resources/FacturaResource/Pages/CreateFactura.php`
-   `app/Filament/Resources/FacturaResource/Pages/ViewFactura.php`
-   `app/Filament/Resources/FacturaResource/Pages/EditFactura.php`

### Seeders (2 archivos)

-   `database/seeders/TimbradoSeeder.php`
-   `database/seeders/FacturaSeeder.php`

### Documentación (1 archivo)

-   `ESPECIFICACION_FACTURAS.md` (este archivo)

**Total:** 18 archivos

## 12. Conclusión

El módulo de Facturas implementa un sistema completo de facturación con:

✅ **Dos flujos de generación:** Desde presupuestos aprobados o facturación directa.
✅ **Integración automática con Libro IVA:** Separación de IVA 5%, 10% y Exentas.
✅ **Integración automática con Cuenta Corriente:** Saldos para ventas a crédito.
✅ **Integración automática con Caja:** Ingresos para ventas a contado.
✅ **Control de timbrados SET:** Validación de vigencia y auto-incremento de números.
✅ **Cálculos automáticos:** IVA incluido, descuentos, totales por tasa.
✅ **Filament Resource completo:** Formulario dinámico, validaciones, notificaciones.
✅ **Seeders para testing:** Datos de prueba realistas.

El sistema cumple con las normativas tributarias paraguayas (SET) y proporciona trazabilidad completa de todas las operaciones de venta.

---

**Fecha de creación:** 10/11/2025  
**Versión:** 1.0  
**Desarrollado para:** SysAlto Piston - Sistema de Gestión Automotriz
