<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresupuestoVentaResource\Pages;
use App\Models\Articulos;
use App\Models\Diagnostico;
use App\Models\PresupuestoVenta;
use App\Models\RecepcionVehiculo;
use App\Models\Promocion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => auth()->user()->cod_sucursal ?? null),

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
                            ->searchable(['id', 'recepcionVehiculo.vehiculo.matricula', 'recepcionVehiculo.cliente.nombres'])
                            ->getOptionLabelFromRecordUsing(function (?Diagnostico $record): ?string {
                                if (! $record) {
                                    return null;
                                }

                                $chapa = $record->recepcionVehiculo?->vehiculo?->matricula ?? 'Sin chapa';
                                $cliente = $record->recepcionVehiculo?->cliente?->nombres ?? 'Sin cliente';

                                return sprintf('#%s · %s · %s', $record->id, $chapa, Str::limit($cliente, 30));
                            })
                            ->default(fn () => request()->integer('diagnostico_id'))
                            ->disabled(fn () => request()->has('diagnostico_id'))
                            ->placeholder('Selecciona el diagnóstico')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (! $state) {
                                    return;
                                }

                                $diagnostico = Diagnostico::with('recepcionVehiculo.cliente')->find($state);

                                if (! $diagnostico) {
                                    return;
                                }

                                $set('recepcion_vehiculo_id', $diagnostico->recepcion_vehiculo_id);
                                $set('cliente_id', $diagnostico->recepcionVehiculo?->cliente?->cod_persona);
                                $set('observaciones_diagnostico', $diagnostico->diagnostico_mecanico ?? '');
                            })
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombres')
                            ->searchable(['nombres', 'apellidos', 'nro_documento'])
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('cod_condicion_compra')
                            ->label('Condición de pago')
                            ->relationship('condicionCompra', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_presupuesto')
                            ->label('Fecha')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Aprobado' => 'Aprobado',
                                'Rechazado' => 'Rechazado',
                            ])
                            ->default('Pendiente')
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
                        Forms\Components\Repeater::make('detalles')
                            ->relationship()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->columns(7)
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->relationship('articulo', 'descripcion')
                                    ->searchable(['descripcion'])
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $articulo = Articulos::find($state);

                                        if (! $articulo) {
                                            return;
                                        }

                                        $set('precio_unitario', (float) ($articulo->precio ?? 0));

                                        if (! $get('cantidad')) {
                                            $set('cantidad', 1);
                                        }

                                        if (! $get('porcentaje_impuesto')) {
                                            $set('porcentaje_impuesto', 10);
                                        }

                                        // Verificar si hay descuento en promoción vigente
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
                                    ->required()
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio Unit.')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('porcentaje_descuento')
                                    ->label('% Desc.')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
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

                                Forms\Components\Hidden::make('descuento_aplicado')
                                    ->default(false),

                                Forms\Components\TextInput::make('monto_descuento')
                                    ->label('Desc. Gs.')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('porcentaje_impuesto')
                                    ->label('% IVA')
                                    ->numeric()
                                    ->default(10)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::syncDetalleImportes($set, $get);
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('monto_impuesto')
                                    ->label('IVA')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(true),
                            ])
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set): void {
                                static::syncTotales($get, $set);
                            })
                            ->deleteAction(function (Forms\Components\Actions\Action $action) {
                                return $action->after(function (callable $get, callable $set): void {
                                    static::syncTotales($get, $set);
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
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('impuestos_totales')
                                    ->label('IVA total')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total general')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0),
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

                TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->searchable()
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

        // Calcular subtotal sin descuento
        $subtotalSinDescuento = round($cantidad * $precio, 2);

        // Calcular monto de descuento
        $montoDescuento = round($subtotalSinDescuento * ($porcentajeDescuento / 100), 2);

        // Calcular subtotal con descuento
        $subtotal = round($subtotalSinDescuento - $montoDescuento, 2);

        // Calcular IVA sobre el subtotal con descuento
        $iva = round($subtotal * ($porcentajeIva / 100), 2);

        $set('monto_descuento', $montoDescuento);
        $set('subtotal', $subtotal);
        $set('monto_impuesto', $iva);
        $set('total', round($subtotal + $iva, 2));

        static::syncTotales($get, $set);
    }

    protected static function syncTotales(callable $get, callable $set): void
    {
        $detalles = collect($get('detalles') ?? []);

        [$subtotal, $iva, $total] = static::summarizeDetalles($detalles->toArray());

        $set('subtotal_general', $subtotal);
        $set('impuestos_totales', $iva);
        $set('total', $total);
    }

    public static function summarizeDetalles(array $detalles): array
    {
        $subtotal = 0.0;
        $iva = 0.0;
        $total = 0.0;

        foreach ($detalles as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
            $iva += (float) ($item['monto_impuesto'] ?? 0);
            $total += (float) ($item['total'] ?? 0);
        }

        return [round($subtotal, 2), round($iva, 2), round($total, 2)];
    }
}
