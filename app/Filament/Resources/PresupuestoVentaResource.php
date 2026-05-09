<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresupuestoVentaResource\Pages;
use App\Models\Articulos;
use App\Models\Cliente;
use App\Models\Diagnostico;
use App\Models\PresupuestoVenta;
use App\Models\Promocion;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Support\Str;

class PresupuestoVentaResource extends Resource
{
    protected static ?string $model = PresupuestoVenta::class;

    protected static ?string $navigationGroup = 'Servicios';
    protected static ?string $navigationLabel = 'Presupuesto de venta';
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cabecera del presupuesto')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('cod_sucursal')
                            ->label('Sucursal')
                            ->relationship('sucursal', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->dehydrated(true)
                            ->required(),

                        Forms\Components\Placeholder::make('usuario_alta')
                            ->label('Usuario Alta')
                            ->content(fn ($record) => $record?->usuario_alta ?? auth()->user()->name ?? 'N/A')
                            ->extraAttributes([
                                'class' => 'text-sm font-medium text-primary-600',
                            ]),

                        Forms\Components\Placeholder::make('fec_alta')
                            ->label('Fecha Alta')
                            ->content(function ($record) {
                                if (!$record || !$record->fec_alta) {
                                    return now()->format('d/m/Y H:i');
                                }
                                return $record->fec_alta instanceof \Carbon\Carbon
                                    ? $record->fec_alta->format('d/m/Y H:i')
                                    : \Carbon\Carbon::parse($record->fec_alta)->format('d/m/Y H:i');
                            })
                            ->extraAttributes([
                                'class' => 'text-sm text-gray-600',
                            ]),

                        Forms\Components\Select::make('diagnostico_id')
                            ->label('Diagnóstico relacionado')
                            ->relationship('diagnostico', 'id')
                            ->searchable(['id', 'recepcionVehiculo.vehiculo.matricula', 'recepcionVehiculo.cliente.persona.nombres'])
                            ->getOptionLabelFromRecordUsing(function (?Diagnostico $record): ?string {
                                if (! $record) {
                                    return null;
                                }
                                $chapa = $record->recepcionVehiculo?->vehiculo?->matricula ?? 'Sin chapa';
                                $cliente = $record->recepcionVehiculo?->cliente?->persona?->nombres ?? 'Sin cliente';
                                return sprintf('#%s · %s · %s', $record->id, $chapa, Str::limit($cliente, 30));
                            })
                            ->default(fn () => request()->integer('diagnostico_id'))
                            ->disabled(fn () => request()->has('diagnostico_id'))
                            ->placeholder('Selecciona el diagnóstico')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get): void {
                                if (! $state) {
                                    $set('cod_cliente', null);
                                    return;
                                }
                                $diagnostico = \App\Models\Diagnostico::with('recepcionVehiculo.cliente.persona')->find($state);
                                if (! $diagnostico || !$diagnostico->recepcionVehiculo) {
                                    $set('cod_cliente', null);
                                    return;
                                }
                                $set('recepcion_vehiculo_id', $diagnostico->recepcion_vehiculo_id);
                                $set('cod_cliente', $diagnostico->recepcionVehiculo->cod_cliente ?? $diagnostico->recepcionVehiculo->cliente_id);
                                $set('observaciones_diagnostico', $diagnostico->diagnostico_mecanico ?? '');
                            })
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Hidden::make('cod_cliente')
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('cliente_nombre')
                            ->label('Cliente (Nombre)')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state, $record, $get) {
                                // Primero intentar traer desde el diagnóstico (recepcion -> cliente)
                                $diagnosticoId = $get('diagnostico_id');
                                if ($diagnosticoId) {
                                    $diagnostico = \App\Models\Diagnostico::with('recepcionVehiculo.cliente.persona')->find($diagnosticoId);
                                    if ($diagnostico?->recepcionVehiculo?->cliente?->persona) {
                                        $persona = $diagnostico->recepcionVehiculo->cliente->persona;
                                        return $persona->razon_social ?: trim($persona->nombres . ' ' . ($persona->apellidos ?? ''));
                                    }
                                }

                                // Si no hay diagnóstico, traer desde cod_cliente
                                $codigoCliente = $get('cod_cliente');
                                if ($codigoCliente) {
                                    $cliente = \App\Models\Cliente::with('persona')->find($codigoCliente);
                                    if ($cliente && $cliente->persona) {
                                        return $cliente->persona->razon_social ?: trim($cliente->persona->nombres . ' ' . ($cliente->persona->apellidos ?? ''));
                                    }
                                }
                                return '';
                            }),

                        Forms\Components\DatePicker::make('fecha_presupuesto')
                            ->label('Fecha')
                            ->default(function () {
                                return Carbon::now('America/Asuncion');
                            })
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('cod_condicion')
                            ->label('Condición de pago')
                            ->relationship('condicion', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Aprobado' => 'Aprobado',
                                'Rechazado' => 'Rechazado',
                            ])
                            ->default('Pendiente')
                            ->live()
                            ->required(),

                        Forms\Components\Textarea::make('observaciones_diagnostico')
                            ->label('Observaciones del mecánico')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (callable $get) => (bool) $get('diagnostico_id'))
                            ->columnSpan(3),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones del presupuesto')
                            ->rows(3)
                            ->placeholder('Observaciones adicionales sobre el presupuesto')
                            ->columnSpan(3),
                    ]),

                Forms\Components\Section::make('Detalle de artículos')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
                        TableRepeater::make('detalles')
                            ->relationship()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->colStyles([
                                'cod_articulo'        => 'width: 40%;',
                                'cantidad'            => 'width: 70px;',
                                'precio_unitario'     => 'width: 120px;',
                                'porcentaje_impuesto' => 'width: 65px;',
                                'porcentaje_descuento'=> 'width: 75px;',
                                'total'               => 'width: 120px;',
                            ])
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->relationship('articulo', 'descripcion')
                                    ->searchable(['descripcion'])
                                    ->preload()
                                    ->placeholder('Buscar artículo...')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if (!$state) {
                                            $set('precio_unitario', null);
                                            $set('cantidad', 0);
                                            $set('porcentaje_impuesto', null);
                                            $set('porcentaje_descuento', 0);
                                            return;
                                        }

                                        $articulo = Articulos::with('impuesto')->find($state);

                                        if (!$articulo) {
                                            return;
                                        }

                                        $precio = floatval($articulo->precio ?? 0);
                                        $set('precio_unitario', $precio);

                                        $set('cantidad', 1);

                                        $porcentajeIva = floatval($articulo->impuesto?->porcentaje ?? 10);
                                        $set('porcentaje_impuesto', $porcentajeIva);

                                        $descuentoPromocion = Promocion::getDescuentoVigente($state);
                                        if ($descuentoPromocion !== null) {
                                            $set('porcentaje_descuento', $descuentoPromocion);
                                            $set('descuento_aplicado', true);
                                        } else {
                                            $set('porcentaje_descuento', 0);
                                            $set('descuento_aplicado', false);
                                        }

                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->minValue(1)
                                    ->step(0.01)
                                    ->required()
                                    ->default(1)
                                    ->extraInputAttributes([
                                        'class' => 'text-right',
                                        'onblur' => '$wire.$refresh()',
                                    ])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($state < 1) {
                                            $set('cantidad', 1);
                                        }
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio')
                                    ->numeric()
                                    ->minValue(1)
                                    ->step(0.01)
                                    ->required()
                                    ->extraInputAttributes(['class' => 'text-right'])
                                    ->live(onBlur: true)
                                    ->dehydrated(true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($state < 0) {
                                            $set('precio_unitario', 0);
                                        }
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('porcentaje_impuesto')
                                    ->label('IVA')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->default(10)
                                    ->extraInputAttributes(['class' => 'text-right'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($state < 0) {
                                            $set('porcentaje_impuesto', 0);
                                        }
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('porcentaje_descuento')
                                    ->label('% Desc.')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->extraInputAttributes(['class' => 'text-right'])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($state < 0) {
                                            $set('porcentaje_descuento', 0);
                                        }
                                        static::syncDetalleImportes($set, $get);
                                    })
                                    ->extraAttributes(function (callable $get) {
                                        if ($get('descuento_aplicado')) {
                                            return [
                                                'class' => 'bg-green-50 border-green-300',
                                                'title' => 'Descuento automático por promoción vigente',
                                            ];
                                        }
                                        return [];
                                    }),

                                Forms\Components\TextInput::make('monto_total')
                                    ->label('Total')
                                    ->numeric()
                                    ->default(0)
                                    ->extraInputAttributes(['class' => 'text-right font-semibold'])
                                    ->readOnly()
                                    ->dehydrated(false),

                                Forms\Components\Hidden::make('descuento_aplicado')->default(false),
                                Forms\Components\Hidden::make('monto_descuento')->default(0),
                                Forms\Components\Hidden::make('subtotal')->default(0),
                                Forms\Components\Hidden::make('monto_impuesto')->default(0),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (!$state || !is_array($state)) {
                                    return;
                                }
                                
                                $articulos = [];
                                foreach ($state as $index => $detalle) {
                                    $codArticulo = $detalle['cod_articulo'] ?? null;
                                    if ($codArticulo) {
                                        if (in_array($codArticulo, $articulos)) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Artículo duplicado')
                                                ->body('El artículo ya se encuentra en el detalle. Se eliminará la fila duplicada.')
                                                ->danger()
                                                ->send();
                                            unset($state[$index]);
                                            $set('detalles', array_values($state));
                                            return;
                                        }
                                        $articulos[] = $codArticulo;
                                    }
                                }
                                
                                static::syncTotales($state, $set);
                            })
                            ->deleteAction(function (Forms\Components\Actions\Action $action) {
                                return $action->after(function (callable $get, callable $set): void {
                                    static::syncTotales($get('detalles') ?? [], $set);
                                });
                            })
                            ->addActionLabel('Agregar artículo')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->collapsed(false),
                    ]),

                Forms\Components\Section::make('Resumen de importes')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_general')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Gs.')
                                    ->extraInputAttributes(['class' => 'text-right'])
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated(false)
                                    ->live(),

                                Forms\Components\TextInput::make('impuestos_totales')
                                    ->label('IVA total')
                                    ->numeric()
                                    ->prefix('Gs.')
                                    ->extraInputAttributes(['class' => 'text-right'])
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated(false)
                                    ->live(),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total general')
                                    ->numeric()
                                    ->prefix('Gs.')
                                    ->extraInputAttributes(['class' => 'text-right font-semibold'])
                                    ->readOnly()
                                    ->default(0)
                                    ->live(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('N.º')
                    ->sortable(),

                TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['persona.nombres', 'persona.apellidos'])
                    ->limit(30),

                TextColumn::make('recepcionVehiculo.vehiculo.matricula')
                    ->label('Chapa')
                    ->toggleable(),

                TextColumn::make('fecha_presupuesto')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('PYG')
                    ->sortable(),

                BadgeColumn::make('estado')
                    ->colors([
                        'warning' => 'Pendiente',
                        'success' => 'Aprobado',
                        'danger' => 'Rechazado',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Aprobado' => 'Aprobado',
                        'Rechazado' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (PresupuestoVenta $record): bool => $record->estado !== 'Aprobado')
                        ->action(function (PresupuestoVenta $record): void {
                            $record->update(['estado' => 'Aprobado']);
                        })
                        ->successNotificationTitle('Presupuesto aprobado correctamente'),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ]);

    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresupuestoVentas::route('/'),
            'create' => Pages\CreatePresupuestoVenta::route('/create'),
            'edit' => Pages\EditPresupuestoVenta::route('/{record}/edit'),
        ];
    }

    protected static function syncDetalleImportes(callable $set, callable $get): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $precio = (float) ($get('precio_unitario') ?? 0);
        $porcentajeDescuento = (float) ($get('porcentaje_descuento') ?? 0);
        $porcentajeIva = (float) ($get('porcentaje_impuesto') ?? 0);

        if (empty($get('cod_articulo'))) {
            $set('monto_total', 0);
            $set('monto_descuento', 0);
            $set('subtotal', 0);
            $set('monto_impuesto', 0);
            return;
        }

        // 1. Total línea (Precio final * Cantidad)
        $totalLinea = $cantidad * $precio;

        // 2. Descuento sobre el total línea
        $montoDescuento = round($totalLinea * ($porcentajeDescuento / 100), 2);
        $totalConDescuento = $totalLinea - $montoDescuento;

        // 3. Extraer IVA (El precio ya incluye IVA)
        // Subtotal = TotalConDescuento / (1 + IVA/100)
        $factorIva = 1 + ($porcentajeIva / 100);
        $subtotal = round($totalConDescuento / $factorIva, 2);
        $montoImpuesto = round($totalConDescuento - $subtotal, 2);

        $set('monto_descuento', $montoDescuento);
        $set('subtotal', $subtotal);
        $set('monto_impuesto', $montoImpuesto);
        $set('monto_total', round($totalConDescuento, 2));
    }

    protected static function syncTotales(array $detalles, callable $set): void
    {
        [$subtotal, $iva, $total] = static::summarizeDetalles($detalles);

        $set('subtotal_general', $subtotal);
        $set('impuestos_totales', $iva);
        // El total general es la suma de subtotal + IVA
        $set('total', round($subtotal + $iva, 2));
    }

    public static function summarizeDetalles(array $detalles): array
    {
        $subtotal = 0.0;
        $iva = 0.0;
        $total = 0.0;

        foreach ($detalles as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
            $iva += (float) ($item['monto_impuesto'] ?? 0);
            $total += (float) ($item['monto_total'] ?? 0);
        }

        return [round($subtotal, 2), round($iva, 2), round($total, 2)];
    }
}
