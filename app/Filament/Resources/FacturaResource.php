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

    protected static ?string $navigationGroup = 'Gestión Ventas';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Factura')
                    ->schema([
                        // Sucursal
                        Forms\Components\TextInput::make('sucursal_display')
                            ->label('Sucursal')
                            ->default(function () {
                                $user = \Illuminate\Support\Facades\Auth::user();
                                if ($user && $user->cod_sucursal) {
                                    $sucursal = \App\Models\Sucursal::find($user->cod_sucursal);
                                    return $sucursal?->descripcion ?? 'Sin sucursal';
                                }
                                return 'Sin sucursal';
                            })
                            ->formatStateUsing(fn ($state, $record) => $record?->sucursal?->descripcion ?? $state ?? 'Sin sucursal')
                            ->disabled()
                            ->dehydrated(false),

                        // Timbrado (Autocompletado automáticamente)
                        Forms\Components\TextInput::make('timbrado_display')
                            ->label('Timbrado')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function () {
                                $timbrado = \App\Models\Timbrado::obtenerTimbradoActivo('FAC');
                                if (!$timbrado) {
                                    return null;
                                }
                                return $timbrado->numero_timbrado;
                            })
                            ->placeholder('Sin timbrado')
                            ->helperText(function () {
                                $timbrado = \App\Models\Timbrado::obtenerTimbradoActivo('FAC');
                                if (!$timbrado) {
                                    return '⚠️ No hay timbrado activo. Contacte al administrador.';
                                }
                                return "Vigente hasta: {$timbrado->fecha_fin_vigencia->format('d/m/Y')} — Disp: {$timbrado->numeros_disponibles}";
                            }),

                        // Serie y próximo número de factura
                        Forms\Components\TextInput::make('serie_factura')
                            ->label('Serie / Próximo Nro')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function () {
                                $timbrado = \App\Models\Timbrado::obtenerTimbradoActivo('FAC');
                                if (!$timbrado) {
                                    return null;
                                }
                                $numero = $timbrado->obtenerSiguienteNumero();
                                return "{$timbrado->establecimiento}-{$timbrado->punto_expedicion}-{$numero}";
                            })
                            ->placeholder('Sin timbrado asignado'),

                        // Campo oculto para almacenar el cod_timbrado real
                        Forms\Components\Hidden::make('cod_timbrado')
                            ->default(fn () => \App\Models\Timbrado::obtenerTimbradoActivo('FAC')?->cod_timbrado)
                            ->dehydrated(),

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
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

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
                                        return Articulos::where('activo', 'A')
                                            ->limit(20)
                                            ->get()
                                            ->mapWithKeys(function ($articulo) {
                                                return [$articulo->cod_articulo => "{$articulo->cod_articulo} - {$articulo->descripcion}"];
                                            });
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Articulos::where('activo', 'A')
                                            ->where(function ($query) use ($search) {
                                                $query->where('descripcion', 'ilike', "%{$search}%")
                                                    ->orWhere('cod_articulo', 'ilike', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($articulo) {
                                                return [$articulo->cod_articulo => "{$articulo->cod_articulo} - {$articulo->descripcion}"];
                                            });
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $articulo = Articulos::find($value);
                                        return $articulo
                                            ? "{$articulo->cod_articulo} - {$articulo->descripcion}"
                                            : "Código #{$value}";
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($state) {
                                            $articulo = Articulos::find($state);
                                            if ($articulo) {
                                                $set('descripcion', $articulo->descripcion);
                                                $set('cantidad', 1);

                                                $precioUnitario = floatval($articulo->precio ?? 0);
                                                $set('precio_unitario', $precioUnitario);

                                                // Calcular automáticamente el detalle
                                                $cantidad = 1;
                                                $porcentajeDescuento = floatval($get('porcentaje_descuento') ?? 0);

                                                $importeBruto = $cantidad * $precioUnitario;
                                                $montoDescuento = ($importeBruto * $porcentajeDescuento) / 100;
                                                $subtotal = $importeBruto - $montoDescuento;
                                                $montoIva = ($subtotal * 10) / 110;

                                                $set('monto_descuento', round($montoDescuento, 2));
                                                $set('subtotal', round($subtotal, 2));
                                                $set('porcentaje_iva', 10);
                                                $set('monto_iva', round($montoIva, 2));
                                                $set('total', round($subtotal, 2));

                                                // Recalcular totales de la factura
                                                self::recalcularTotalesDesdeItem($set, $get);
                                            }
                                        }
                                    })
                                    ->disabled(fn (string $operation): bool =>
                                        $operation === 'edit' || request()->has('orden_servicio_id') || request()->has('presupuesto_venta_id'))
                                    ->columnSpan(4),

                                Forms\Components\Hidden::make('descripcion')
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calcularDetalle($set, $get);
                                        self::recalcularTotalesDesdeItem($set, $get);
                                    })
                                    ->disabled(fn (string $operation): bool =>
                                        $operation === 'edit' || request()->has('orden_servicio_id') || request()->has('presupuesto_venta_id'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('P/U')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('Gs.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calcularDetalle($set, $get);
                                        self::recalcularTotalesDesdeItem($set, $get);
                                    })
                                    ->disabled(fn (string $operation): bool =>
                                        $operation === 'edit' || request()->has('orden_servicio_id') || request()->has('presupuesto_venta_id'))
                                    ->columnSpan(2),

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
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calcularDetalle($set, $get);
                                        self::recalcularTotalesDesdeItem($set, $get);
                                    })
                                    ->disabled(fn (string $operation): bool =>
                                        $operation === 'edit' || request()->has('orden_servicio_id') || request()->has('presupuesto_venta_id'))
                                    ->columnSpan(1),

                                // Campos ocultos de descuento (se aplican automáticamente al calcular)
                                Forms\Components\Hidden::make('porcentaje_descuento')
                                    ->default(0)
                                    ->dehydrated(),

                                Forms\Components\Hidden::make('monto_descuento')
                                    ->default(0)
                                    ->dehydrated(),

                                // Campos ocultos de totales calculados
                                Forms\Components\Hidden::make('subtotal')
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('monto_iva')
                                    ->label('IVA')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),

                                Forms\Components\Hidden::make('porcentaje_iva')
                                    ->dehydrated(),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Item')
                            ->collapsible()
                            ->collapsed(fn (string $operation): bool => $operation !== 'create')
                            ->itemLabel(function (array $state): ?string {
                                $desc = $state['descripcion'] ?? 'Nuevo Item';
                                $total = $state['total'] ?? 0;
                                return "{$desc} — Gs. " . number_format($total, 0, ',', '.');
                            })
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calcularTotalesFactura($set, $get);
                            })
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(fn (Set $set, Get $get) => self::calcularTotalesFactura($set, $get)),
                            )
                            ->addable(fn (): bool => !request()->has('orden_servicio_id') && !request()->has('presupuesto_venta_id'))
                            ->deletable(fn (): bool => !request()->has('orden_servicio_id') && !request()->has('presupuesto_venta_id'))
                            ->reorderable(fn (): bool => !request()->has('orden_servicio_id') && !request()->has('presupuesto_venta_id'))
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

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('usuario_alta')
                            ->label('Usuario Alta')
                            ->default(fn () => \Illuminate\Support\Facades\Auth::user()?->name ?? 'Sistema')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('fecha_alta')
                            ->label('Fecha Alta')
                            ->default(fn () => now()->format('d/m/Y H:i:s'))
                            ->disabled()
                            ->dehydrated(false),
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
            $subtotal = floatval($detalle['subtotal'] ?? 0); // Con IVA incluido
            $montoIva = floatval($detalle['monto_iva'] ?? 0);
            $base = $subtotal - $montoIva; // Base imponible sin IVA

            if ($tipoIva === '10') {
                $subtotalGravado10 += $base;
                $totalIva10 += $montoIva;
            } elseif ($tipoIva === '5') {
                $subtotalGravado5 += $base;
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

    /**
     * Recalcula los totales de la factura desde dentro de un item del Repeater
     * Usa paths relativos (../../) para acceder al estado del padre
     */
    protected static function recalcularTotalesDesdeItem(Set $set, Get $get): void
    {
        $detalles = collect($get('../../detalles') ?? []);

        $subtotalGravado10 = 0;
        $totalIva10 = 0;
        $subtotalGravado5 = 0;
        $totalIva5 = 0;
        $subtotalExenta = 0;

        foreach ($detalles as $detalle) {
            $tipoIva = $detalle['tipo_iva'] ?? '10';
            $subtotal = floatval($detalle['subtotal'] ?? 0);
            $montoIva = floatval($detalle['monto_iva'] ?? 0);
            $base = $subtotal - $montoIva;

            if ($tipoIva === '10') {
                $subtotalGravado10 += $base;
                $totalIva10 += $montoIva;
            } elseif ($tipoIva === '5') {
                $subtotalGravado5 += $base;
                $totalIva5 += $montoIva;
            } elseif ($tipoIva === 'Exenta') {
                $subtotalExenta += $subtotal;
            }
        }

        $totalGeneral = $subtotalGravado10 + $totalIva10 + $subtotalGravado5 + $totalIva5 + $subtotalExenta;

        $set('../../subtotal_gravado_10', round($subtotalGravado10, 2));
        $set('../../total_iva_10', round($totalIva10, 2));
        $set('../../subtotal_gravado_5', round($subtotalGravado5, 2));
        $set('../../total_iva_5', round($totalIva5, 2));
        $set('../../subtotal_exenta', round($subtotalExenta, 2));
        $set('../../total_general', round($totalGeneral, 2));
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

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_alta')
                    ->label('Fecha Alta')
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
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Factura $record): string => route('facturas.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderBy('fecha_factura', 'desc')->orderBy('numero_factura', 'desc'));
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
