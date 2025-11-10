# CAMBIOS PENDIENTES PARA MÓDULO DE FACTURAS

## ✅ Completado

1. **Migración `caja_timbrado`** - Tabla para asignar timbrados a cajas
2. **Migración `factura_vencimientos`** - Tabla para cuotas y vencimientos
3. **Migración** - Agregar `cod_condicion_compra` a facturas
4. **Modelo `CajaTimbrado`** - Con método `obtenerTimbradoDeCaja()`
5. **Modelo `FacturaVencimiento`** - Con método `registrarPago()`
6. **Modelo `Caja`** - Agregar relación `timbrados()` y `timbradoActivo()`
7. **Modelo `Factura`** - Agregar:
    - Relación `condicionCompra()`
    - Relación `vencimientos()`
    - Método `generarVencimientos()` automático
8. **Modelos `PresupuestoVenta` y `OrdenServicio`** - Agregar relación `facturas()`

## 🔄 Cambios Necesarios en FacturaResource

Debido a que el archivo es muy extenso (539 líneas), aquí están los cambios clave que necesitas:

### 1. Agregar imports

```php
use App\Models\CondicionCompra;
use App\Models\OrdenServicio;
use App\Models\CajaTimbrado;
use App\Models\AperturaCaja;
```

### 2. Modificar Sección "Tipo de Factura"

Reemplazar el toggle único por tres opciones:

```php
Forms\Components\Section::make('Origen de la Factura')
    ->schema([
        Forms\Components\Radio::make('tipo_origen')
            ->label('Seleccione el origen')
            ->options([
                'presupuesto' => 'Desde Presupuesto',
                'orden_servicio' => 'Desde Orden de Servicio',
                'directa' => 'Factura Directa',
            ])
            ->default('directa')
            ->live()
            ->required()
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state !== 'presupuesto') {
                    $set('presupuesto_venta_id', null);
                }
                if ($state !== 'orden_servicio') {
                    $set('orden_servicio_id', null);
                }
                if ($state !== 'directa') {
                    $set('cod_cliente', null);
                }
            }),
    ])
    ->columns(1)
    ->visible(fn (string $operation) => $operation === 'create'),
```

### 3. Agregar Select de Orden de Servicio

Después del select de presupuesto, agregar:

```php
Forms\Components\Select::make('orden_servicio_id')
    ->label('Orden de Servicio')
    ->options(function () {
        return OrdenServicio::whereIn('estado_trabajo', ['En Proceso', 'Finalizado'])
            ->whereDoesntHave('facturas')
            ->with('cliente')
            ->get()
            ->mapWithKeys(function ($os) {
                $clienteNombre = $os->cliente->nombre_completo ?? 'Sin cliente';
                return [$os->id => "OS #{$os->id} - {$clienteNombre} - Gs. " . number_format($os->total, 0, ',', '.')];
            });
    })
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(function (Set $set, Get $get, $state) {
        if ($state) {
            $os = OrdenServicio::with(['detalles.articulo', 'cliente'])->find($state);
            if ($os) {
                $set('cod_cliente', $os->cliente_id);
                $set('fecha_factura', now()->toDateString());

                // Cargar detalles de la OS
                $detalles = $os->detalles->map(function ($detalle) {
                    return [
                        'cod_articulo' => $detalle->articulo_id,
                        'descripcion' => $detalle->descripcion,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_venta,
                        'porcentaje_descuento' => 0,
                        'tipo_iva' => '10',
                    ];
                })->toArray();

                $set('detalles', $detalles);
            }
        }
    })
    ->visible(fn (Get $get) => $get('tipo_origen') === 'orden_servicio')
    ->required(fn (Get $get) => $get('tipo_origen') === 'orden_servicio')
    ->disabled(fn (string $operation) => $operation === 'edit'),
```

### 4. Reemplazar Select de Condición de Venta

Cambiar el select simple por uno que use la tabla `condicion_compra`:

```php
// Reemplazar esto:
Forms\Components\Select::make('condicion_venta')
    ->label('Condición de Venta')
    ->options([
        'Contado' => 'Contado',
        'Crédito' => 'Crédito',
    ])
    ->required()
    ->default('Contado')
    ->disabled(fn (string $operation) => $operation === 'edit'),

// Por esto:
Forms\Components\Select::make('cod_condicion_compra')
    ->label('Condición de Compra')
    ->options(function () {
        return CondicionCompra::where('activo', true)
            ->get()
            ->mapWithKeys(function ($condicion) {
                $tipo = $condicion->dias_cuota == 0 ? 'CONTADO' : "CRÉDITO ({$condicion->dias_cuota} días)";
                return [$condicion->cod_condicion_compra => "{$condicion->descripcion} - {$tipo}"];
            });
    })
    ->required()
    ->live()
    ->afterStateUpdated(function (Set $set, $state) {
        if ($state) {
            $condicion = CondicionCompra::find($state);
            if ($condicion) {
                // Determinar si es contado o crédito según días_cuota
                $set('condicion_venta', $condicion->dias_cuota == 0 ? 'Contado' : 'Crédito');
            }
        }
    })
    ->disabled(fn (string $operation) => $operation === 'edit'),

// Agregar campo hidden para condicion_venta
Forms\Components\Hidden::make('condicion_venta')
    ->default('Contado'),
```

### 5. Modificar Select de Timbrado

El timbrado debe obtenerse de la caja del cajero actual:

```php
Forms\Components\Select::make('cod_timbrado')
    ->label('Timbrado')
    ->options(function () {
        // Obtener el cajero actual
        $cajero = auth()->user()->empleado ?? null;

        if (!$cajero) {
            return [];
        }

        // Buscar caja abierta del cajero
        $aperturaCaja = AperturaCaja::where('estado', 'Abierta')
            ->where('cod_cajero', $cajero->cod_empleado)
            ->first();

        if (!$aperturaCaja) {
            return [];
        }

        // Obtener timbrado de esa caja
        $timbrado = $aperturaCaja->caja->timbradoActivo();

        if (!$timbrado || !$timbrado->estaVigente()) {
            return [];
        }

        return [
            $timbrado->cod_timbrado => "{$timbrado->numero_timbrado} ({$timbrado->establecimiento}-{$timbrado->punto_expedicion}) - Disponibles: {$timbrado->numeros_disponibles}"
        ];
    })
    ->required()
    ->searchable()
    ->preload()
    ->disabled(fn (string $operation) => $operation === 'edit')
    ->helperText('El timbrado corresponde a la caja que tienes abierta actualmente.'),
```

### 6. Actualizar mutateFormDataBeforeCreate en CreateFactura

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    // Remover campos virtuales
    unset($data['desde_presupuesto']);
    unset($data['tipo_origen']);

    // Asegurar que el estado sea 'Emitida'
    $data['estado'] = 'Emitida';

    return $data;
}
```

## 📝 Seeders Pendientes

### CajaTimbradoSeeder

Crear `database/seeders/CajaTimbradoSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CajaTimbrado;
use App\Models\Caja;
use App\Models\Timbrado;

class CajaTimbradoSeeder extends Seeder
{
    public function run(): void
    {
        if (CajaTimbrado::count() > 0) {
            $this->command->info('❌ Ya existen asignaciones caja-timbrado. Saltando seeder...');
            return;
        }

        $this->command->info('🔗 Asignando timbrados a cajas...');

        // Obtener cajas y timbrados
        $cajas = Caja::all();
        $timbrados = Timbrado::vigentes()->get();

        if ($cajas->isEmpty() || $timbrados->isEmpty()) {
            $this->command->error('❌ No hay cajas o timbrados disponibles.');
            return;
        }

        // Asignar un timbrado a cada caja
        foreach ($cajas as $index => $caja) {
            $timbrado = $timbrados[$index % $timbrados->count()];

            CajaTimbrado::create([
                'cod_caja' => $caja->cod_caja,
                'cod_timbrado' => $timbrado->cod_timbrado,
                'activo' => true,
                'fecha_asignacion' => now(),
            ]);
        }

        $this->command->info('✅ Asignaciones creadas exitosamente.');
    }
}
```

Agregar al DatabaseSeeder:

```php
$this->call([
    // ... otros seeders
    CajaSeeder::class,
    TimbradoSeeder::class,
    CajaTimbradoSeeder::class, // NUEVO
    AperturaCajaSeeder::class,
    FacturaSeeder::class,
]);
```

## 🚀 Pasos para Aplicar

1. **Ejecutar migraciones nuevas:**

```bash
php artisan migrate
```

2. **Ejecutar seeders:**

```bash
php artisan db:seed --class=TimbradoSeeder
php artisan db:seed --class=CajaTimbradoSeeder
```

3. **Actualizar FacturaResource.php** con los cambios indicados arriba

4. **Probar el flujo completo:**
    - Abrir una caja
    - Crear factura (debe usar el timbrado de esa caja)
    - Seleccionar condición de compra
    - Verificar que se generan vencimientos si es crédito

## 📊 Comportamiento Esperado

-   **Contado (dias_cuota = 0):** No genera vencimientos, registra ingreso en caja
-   **30 días (dias_cuota = 30):** Genera 1 cuota con vencimiento a 30 días
-   **60 días (dias_cuota = 60):** Genera 2 cuotas (30 y 60 días)
-   **90 días (dias_cuota = 90):** Genera 3 cuotas (30, 60 y 90 días)

Cada cajero solo puede facturar con el timbrado asignado a su caja abierta.
