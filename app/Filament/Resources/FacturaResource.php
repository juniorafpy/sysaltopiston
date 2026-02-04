<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacturaResource\Pages;
use App\Models\Factura;
use App\Models\Timbrado;
use App\Models\Personas;
use App\Models\PresupuestoVenta;
use App\Models\OrdenServicio;
use App\Models\CondicionCompra;
use App\Models\CajaTimbrado;
use App\Models\AperturaCaja;
use App\Models\Articulos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;

class FacturaResource extends Resource
{
    protected static ?string $model = Factura::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Facturas';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo de Factura')
                    ->schema([
                        Forms\Components\Radio::make('origen_factura')
                            ->label('Origen de la Factura')
                            ->options([
                                'presupuesto' => 'Desde Presupuesto de Venta',
                                'orden_servicio' => 'Desde Orden de Servicio',
                                'directa' => 'Factura Directa',
                            ])
                            ->default('directa')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                // Limpiar campos según el origen
                                if ($state !== 'presupuesto') {
                                    $set('presupuesto_venta_id', null);
                                }
                                if ($state !== 'orden_servicio') {
                                    $set('orden_servicio_id', null);
                                }
                                if ($state !== 'directa') {
                                    // Limpiar cliente si viene desde presupuesto u OS
                                } else {
                                    $set('cod_cliente', null);
                                    $set('detalles', []);
                                }
                            })
                            ->required(),
                    ])
                    ->columns(1)
                    ->visible(fn (string $operation) => $operation === 'create'),

                Forms\Components\Section::make('Información de la Factura')
                    ->schema([
                        // Presupuesto Select (solo si desde_presupuesto = true)
                        Forms\Components\Select::make('presupuesto_venta_id')
                            ->label('Presupuesto')
                            ->options(function () {
                                return PresupuestoVenta::where('estado', 'Aprobado')
                                    ->whereDoesntHave('facturas')
                                    ->with('cliente')
                                    ->get()
                                    ->mapWithKeys(function ($presupuesto) {
                                        $clienteNombre = $presupuesto->cliente->nombre_completo ?? 'Sin cliente';
                                        return [$presupuesto->id => "#{$presupuesto->id} - {$clienteNombre} - Gs. " . number_format($presupuesto->total, 0, ',', '.')];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $presupuesto = PresupuestoVenta::with(['detalles.articulo', 'cliente'])->find($state);
                                    if ($presupuesto) {
                                        $set('cod_cliente', $presupuesto->cliente_id);
                                        $set('fecha_factura', now()->toDateString());

                                        // Cargar detalles del presupuesto
                                        $detalles = $presupuesto->detalles->map(function ($detalle) {
                                            return [
                                                'cod_articulo' => $detalle->articulo_id,
                                                'descripcion' => $detalle->articulo->descripcion ?? $detalle->descripcion,
                                                'cantidad' => $detalle->cantidad,
                                                'precio_unitario' => $detalle->precio_unitario,
                                                'porcentaje_descuento' => 0,
                                                'tipo_iva' => '10',
                                            ];
                                        })->toArray();

                                        $set('detalles', $detalles);
                                    }
                                }
                            })
                            ->visible(fn (Get $get) => $get('origen_factura') === 'presupuesto')
                            ->required(fn (Get $get) => $get('origen_factura') === 'presupuesto')
                            ->disabled(fn (string $operation) => $operation === 'edit'),

                        // Orden de Servicio Select
                        Forms\Components\Select::make('orden_servicio_id')
                            ->label('Orden de Servicio')
                            ->options(function () {
                                return OrdenServicio::where('estado', 'Finalizado')
                                    ->whereDoesntHave('facturas')
                                    ->with('cliente')
                                    ->get()
                                    ->mapWithKeys(function ($orden) {
                                        $clienteNombre = $orden->cliente->nombre_completo ?? 'Sin cliente';
                                        $vehiculo = $orden->recepcion?->vehiculo;
                                        $vehiculoInfo = $vehiculo ? " - {$vehiculo->matricula}" : '';
                                        return [$orden->id => "OS #{$orden->id} - {$clienteNombre}{$vehiculoInfo}"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $orden = OrdenServicio::with(['detalles.articulo', 'cliente'])->find($state);
                                    if ($orden) {
                                        $set('cod_cliente', $orden->cliente_id);
                                        $set('fecha_factura', now()->toDateString());

                                        // Cargar detalles de la orden de servicio
                                        $detalles = $orden->detalles->map(function ($detalle) {
                                            return [
                                                'cod_articulo' => $detalle->articulo_id,
                                                'descripcion' => $detalle->articulo->descripcion ?? $detalle->descripcion,
                                                'cantidad' => $detalle->cantidad,
                                                'precio_unitario' => $detalle->precio_unitario,
                                                'porcentaje_descuento' => 0,
                                                'tipo_iva' => '10',
                                            ];
                                        })->toArray();

                                        $set('detalles', $detalles);
                                    }
                                }
                            })
                            ->visible(fn (Get $get) => $get('origen_factura') === 'orden_servicio')
                            ->required(fn (Get $get) => $get('origen_factura') === 'orden_servicio')
                            ->disabled(fn (string $operation) => $operation === 'edit'),

                        // Timbrado
                        Forms\Components\Select::make('cod_timbrado')
                            ->label('Timbrado')
                            ->options(function () {
                                $user = auth()->user();
                                if (!$user) {
                                    return [];
                                }

                                $cajero = $user->empleado;
                                if (!$cajero) {
                                    return [];
                                }

                                $aperturaCaja = AperturaCaja::where('estado', 'Abierta')
                                    ->where('cod_cajero', $cajero->cod_empleado)
                                    ->first();

                                if (!$aperturaCaja) {
                                    return [];
                                }

                                $timbrado = $aperturaCaja->caja->timbradoActivo();
                                if (!$timbrado) {
                                    return [];
                                }

                                return [
                                    $timbrado->cod_timbrado => "{$timbrado->numero_timbrado} ({$timbrado->establecimiento}-{$timbrado->punto_expedicion}) - Disponibles: {$timbrado->numeros_disponibles}"
                                ];
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText(function () {
                                $user = auth()->user();
                                if (!$user) {
                                    return 'Error: Usuario no autenticado';
                                }

                                $cajero = $user->empleado;
                                if (!$cajero) {
                                    return 'Error: Tu usuario no está asociado a un empleado';
                                }

                                $aperturaCaja = AperturaCaja::where('estado', 'Abierta')
                                    ->where('cod_cajero', $cajero->cod_empleado)
                                    ->first();

                                if (!$aperturaCaja) {
                                    return 'No tienes una caja abierta. Por favor, realiza la apertura de caja primero.';
                                }

                                $timbrado = $aperturaCaja->caja->timbradoActivo();
                                if (!$timbrado) {
                                    return 'Tu caja no tiene un timbrado asignado. Contacta al administrador.';
                                }

                                return 'Timbrado de tu caja actual';
                            })
                            ->disabled(fn (string $operation) => $operation === 'edit'),

                        // Número de Factura (automático)
                        Forms\Components\TextInput::make('numero_factura')
                            ->label('Número de Factura')
                            ->disabled()
                            ->visible(fn (string $operation) => $operation === 'edit')
                            ->dehydrated(false),

                        // Fecha
                        Forms\Components\DatePicker::make('fecha_factura')
                            ->label('Fecha de Factura')
                            ->required()
                            ->default(now())
                            ->disabled(fn (string $operation) => $operation === 'edit'),

                        // Cliente
                        Forms\Components\Select::make('cod_cliente')
                            ->label('Cliente')
                            ->options(function () {
                                return Personas::where('ind_activo', 'S')
                                    ->get()
                                    ->mapWithKeys(function ($persona) {
                                        $ruc = $persona->nro_documento ?? 'Sin RUC';
                                        return [$persona->cod_persona => "{$persona->nombre_completo} - {$ruc}"];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get, string $operation): bool =>
                                $operation === 'edit' || ($get('origen_factura') !== 'directa' && $get('origen_factura') !== null)
                            ),

                        // Condición de Compra
                        Forms\Components\Select::make('cod_condicion_compra')
                            ->label('Condición de Compra')
                            ->relationship('condicionCompra', 'descripcion')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $condicion = CondicionCompra::find($state);
                                    if ($condicion) {
                                        // Establecer condicion_venta según cant_cuota
                                        $set('condicion_venta', $condicion->cant_cuota == 0 ? 'Contado' : 'Crédito');
                                    }
                                }
                            })
                            ->disabled(fn (string $operation) => $operation === 'edit'),

                        // Observaciones
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detalles de la Factura')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->relationship('detalles')
                            ->schema([
                                Forms\Components\Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->options(function () {
                                        return Articulos::where('activo', true)
                                            ->get()
                                            ->mapWithKeys(function ($articulo) {
                                                return [$articulo->cod_articulo => "{$articulo->descripcion} - Gs. " . number_format($articulo->precio_venta ?? 0, 0, ',', '.')];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $articulo = Articulos::find($state);
                                            if ($articulo) {
                                                $set('descripcion', $articulo->descripcion);
                                                $set('precio_unitario', $articulo->precio_venta ?? 0);
                                                $set('cantidad', 1);
                                            }
                                        }
                                    })
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calcularDetalle($set, $get))
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio Unitario')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calcularDetalle($set, $get))
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('porcentaje_descuento')
                                    ->label('% Desc.')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calcularDetalle($set, $get))
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('tipo_iva')
                                    ->label('IVA')
                                    ->options([
                                        '10' => '10%',
                                        '5' => '5%',
                                        'Exenta' => 'Exenta',
                                    ])
                                    ->required()
                                    ->default('10')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calcularDetalle($set, $get))
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('monto_descuento')
                                    ->label('Descuento')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('porcentaje_iva')
                                    ->label('% IVA')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('monto_iva')
                                    ->label('Monto IVA')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['descripcion'] ?? 'Nuevo Item')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calcularTotalesFactura($set, $get);
                            })
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(fn (Set $set, Get $get) => self::calcularTotalesFactura($set, $get)),
                            )
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_gravado_10')
                                    ->label('Gravado 10%')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('subtotal_gravado_10') ?? 0, 0, ',', '.')),

                                Forms\Components\Placeholder::make('total_iva_10')
                                    ->label('IVA 10%')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('total_iva_10') ?? 0, 0, ',', '.')),

                                Forms\Components\TextInput::make('subtotal_gravado_10')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_gravado_5')
                                    ->label('Gravado 5%')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('subtotal_gravado_5') ?? 0, 0, ',', '.')),

                                Forms\Components\Placeholder::make('total_iva_5')
                                    ->label('IVA 5%')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('total_iva_5') ?? 0, 0, ',', '.')),

                                Forms\Components\TextInput::make('subtotal_gravado_5')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_exenta_display')
                                    ->label('Exentas')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('subtotal_exenta') ?? 0, 0, ',', '.')),

                                Forms\Components\Placeholder::make('total_general_display')
                                    ->label('TOTAL GENERAL')
                                    ->content(fn (Get $get): string => 'Gs. ' . number_format($get('total_general') ?? 0, 0, ',', '.'))
                                    ->extraAttributes(['class' => 'font-bold text-xl']),

                                Forms\Components\TextInput::make('subtotal_exenta')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('total_iva_5')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('total_iva_10')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('total_general')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Calcula los valores de un detalle individual
     */
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

        // Calcular IVA
        $porcentajeIva = match ($tipoIva) {
            '10' => 10,
            '5' => 5,
            default => 0,
        };

        // El precio ya incluye IVA, calcular cuánto es el IVA
        $montoIva = match ($tipoIva) {
            '10' => ($subtotal * 10) / 110,
            '5' => ($subtotal * 5) / 105,
            default => 0,
        };

        $total = $subtotal;

        // Setear valores calculados
        $set('monto_descuento', round($montoDescuento, 2));
        $set('subtotal', round($subtotal, 2));
        $set('porcentaje_iva', $porcentajeIva);
        $set('monto_iva', round($montoIva, 2));
        $set('total', round($total, 2));
    }

    /**
     * Calcula los totales de la factura
     */
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

        $totalGeneral = $subtotalGravado10 + $totalIva10 + $subtotalGravado5 + $totalIva5 + $subtotalExenta;

        $set('subtotal_gravado_10', round($subtotalGravado10, 2));
        $set('total_iva_10', round($totalIva10, 2));
        $set('subtotal_gravado_5', round($subtotalGravado5, 2));
        $set('total_iva_5', round($totalIva5, 2));
        $set('subtotal_exenta', round($subtotalExenta, 2));
        $set('total_general', round($totalGeneral, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_factura')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_factura')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('condicion_venta')
                    ->label('Condición')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Contado' => 'success',
                        'Crédito' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_general')
                    ->label('Total')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Emitida' => 'success',
                        'Anulada' => 'danger',
                        'Pagada' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('condicion_venta')
                    ->label('Condición de Venta')
                    ->options([
                        'Contado' => 'Contado',
                        'Crédito' => 'Crédito',
                    ]),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Emitida' => 'Emitida',
                        'Anulada' => 'Anulada',
                        'Pagada' => 'Pagada',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cobrar')
                    ->label('Cobrar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Factura $record): bool =>
                        $record->condicion_venta === 'Crédito' && $record->getSaldoPendiente() > 0
                    )
                    ->url(fn (Factura $record): string =>
                        route('filament.admin.resources.cobros.create', ['cod_factura' => $record->cod_factura])
                    ),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_factura', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturas::route('/'),
            'create' => Pages\CreateFactura::route('/create'),
            'view' => Pages\ViewFactura::route('/{record}'),
            'edit' => Pages\EditFactura::route('/{record}/edit'),
        ];
    }
}
