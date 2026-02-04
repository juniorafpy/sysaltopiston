<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CobroResource\Pages;
use App\Models\Cobro;
use App\Models\Factura;
use App\Models\EntidadBancaria;
use App\Models\Personas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;

class CobroResource extends Resource
{
    protected static ?string $model = Cobro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cobros';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Información de Caja
                Forms\Components\Section::make('Información de Caja')
                    ->schema([
                        Forms\Components\Placeholder::make('apertura_info')
                            ->label('Apertura de Caja Actual')
                            ->content(function () {
                                $usuario = Auth::user();
                                if (!$usuario->empleado) {
                                    return '⚠️ Tu usuario no está asociado a un empleado';
                                }

                                $aperturaCaja = \App\Models\AperturaCaja::where('cod_cajero', $usuario->empleado->cod_empleado)
                                    ->where('fecha_cierre', null)
                                    ->orderBy('cod_apertura', 'desc')
                                    ->first();

                                if (!$aperturaCaja) {
                                    return '❌ No tienes una caja abierta';
                                }

                                return "✅ Apertura N° {$aperturaCaja->cod_apertura} - Fecha: " .
                                       $aperturaCaja->fecha_apertura->format('d/m/Y') .
                                       " - Monto inicial: Gs. " . number_format($aperturaCaja->monto_apertura, 0, ',', '.');
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // Información General
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Select::make('cod_cliente')
                            ->label('Cliente')
                            ->options(function () {
                                // Clientes con facturas a crédito pendientes
                                return Personas::whereHas('facturas', function ($query) {
                                    $query->where('condicion_venta', 'Crédito')
                                          ->where('estado', 'Emitida');
                                })
                                ->get()
                                ->filter(function ($cliente) {
                                    // Verificar que tenga al menos una factura con saldo pendiente
                                    return $cliente->facturas()
                                        ->where('condicion_venta', 'Crédito')
                                        ->where('estado', 'Emitida')
                                        ->get()
                                        ->filter(fn ($factura) => $factura->getSaldoConNotas() > 0)
                                        ->count() > 0;
                                })
                                ->mapWithKeys(function ($cliente) {
                                    return [$cliente->cod_persona => $cliente->nombre_completo];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('fecha_cobro')
                            ->label('Fecha de Cobro')
                            ->default(now())
                            ->required()
                            ->maxDate(now())
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                    ])
                    ->columns(3),

                // Facturas y Cuotas a Cobrar
                Forms\Components\Section::make('Facturas y Cuotas a Cobrar')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('cod_factura')
                                    ->label('Factura')
                                    ->options(function (Get $get) {
                                        $codCliente = $get('../../cod_cliente');
                                        if (!$codCliente) {
                                            return [];
                                        }

                                        return Factura::where('cod_cliente', $codCliente)
                                            ->where('condicion_venta', 'Crédito')
                                            ->where('estado', 'Emitida')
                                            ->with('condicionCompra')
                                            ->get()
                                            ->filter(function ($factura) {
                                                return $factura->getSaldoConNotas() > 0;
                                            })
                                            ->mapWithKeys(function ($factura) {
                                                $saldo = $factura->getSaldoConNotas();
                                                $diasCuota = $factura->condicionCompra->cant_cuota ?? 0;
                                                $cuotasInfo = $diasCuota > 0 ? " ({$diasCuota} días)" : "";

                                                return [
                                                    $factura->cod_factura =>
                                                    "{$factura->numero_factura}{$cuotasInfo} - Saldo: Gs. " .
                                                    number_format($saldo, 0, ',', '.')
                                                ];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $factura = Factura::with('condicionCompra')->find($state);
                                            if ($factura) {
                                                $diasCuota = $factura->condicionCompra->cant_cuota ?? 0;
                                                $numeroCuotas = $diasCuota > 0 ? ($diasCuota / 30) : 1;
                                                $montoPorCuota = $numeroCuotas > 0 ? ($factura->total_general / $numeroCuotas) : $factura->total_general;

                                                $set('numero_cuota', 1);
                                                $set('monto_cuota', $montoPorCuota);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('numero_cuota')
                                    ->label('N° Cuota')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($get('cod_factura') && $state) {
                                            $factura = Factura::with('condicionCompra')->find($get('cod_factura'));
                                            if ($factura) {
                                                $diasCuota = $factura->condicionCompra->cant_cuota ?? 0;
                                                $numeroCuotas = $diasCuota > 0 ? ($diasCuota / 30) : 1;
                                                $montoPorCuota = $numeroCuotas > 0 ? ($factura->total_general / $numeroCuotas) : $factura->total_general;

                                                $set('monto_cuota', $montoPorCuota);
                                            }
                                        }
                                    })
                                    ->helperText('Número de cuota a pagar'),

                                Forms\Components\TextInput::make('monto_cuota')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, callable $set) {
                                        self::calcularTotales($get, $set);
                                    }),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Factura/Cuota')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                $state['cod_factura'] ?
                                "Factura - Cuota {$state['numero_cuota']}" :
                                'Nueva factura/cuota'
                            ),

                        Forms\Components\Placeholder::make('total_detalles')
                            ->label('Total a Cobrar')
                            ->content(function (Get $get) {
                                $detalles = $get('detalles') ?? [];
                                $total = collect($detalles)->sum('monto_cuota');
                                return 'Gs. ' . number_format($total, 0, ',', '.');
                            }),
                    ]),

                // Formas de Pago
                Forms\Components\Section::make('Formas de Pago')
                    ->schema([
                        Forms\Components\Repeater::make('formas_pago')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('tipo_transaccion')
                                    ->label('Tipo')
                                    ->options([
                                        'efectivo' => 'Efectivo',
                                        'tarjeta_credito' => 'Tarjeta de Crédito',
                                        'tarjeta_debito' => 'Tarjeta de Débito',
                                        'cheque' => 'Cheque',
                                        'transferencia' => 'Transferencia',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, callable $set) {
                                        self::calcularTotales($get, $set);
                                    })
                                    ->columnSpan(2),

                                Forms\Components\Select::make('cod_entidad_bancaria')
                                    ->label('Banco')
                                    ->options(EntidadBancaria::activas()->pluck('nombre', 'cod_entidad_bancaria'))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool =>
                                        in_array($get('tipo_transaccion'), ['tarjeta_credito', 'tarjeta_debito', 'cheque', 'transferencia'])
                                    )
                                    ->required(fn (Get $get): bool =>
                                        in_array($get('tipo_transaccion'), ['tarjeta_credito', 'tarjeta_debito', 'cheque', 'transferencia'])
                                    )
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('numero_voucher')
                                    ->label('N° Voucher')
                                    ->maxLength(50)
                                    ->visible(fn (Get $get): bool =>
                                        in_array($get('tipo_transaccion'), ['tarjeta_credito', 'tarjeta_debito'])
                                    )
                                    ->required(fn (Get $get): bool =>
                                        in_array($get('tipo_transaccion'), ['tarjeta_credito', 'tarjeta_debito'])
                                    )
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('numero_cheque')
                                    ->label('N° Cheque')
                                    ->maxLength(50)
                                    ->visible(fn (Get $get): bool => $get('tipo_transaccion') === 'cheque')
                                    ->required(fn (Get $get): bool => $get('tipo_transaccion') === 'cheque')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Forma de Pago')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                $state['tipo_transaccion'] ?
                                ucfirst(str_replace('_', ' ', $state['tipo_transaccion'])) :
                                'Nueva forma de pago'
                            ),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_formas_pago')
                                    ->label('Total Formas de Pago')
                                    ->content(function (Get $get) {
                                        $formasPago = $get('formas_pago') ?? [];
                                        $total = collect($formasPago)->sum('monto');
                                        return 'Gs. ' . number_format($total, 0, ',', '.');
                                    }),

                                Forms\Components\Placeholder::make('diferencia')
                                    ->label('Diferencia')
                                    ->content(function (Get $get) {
                                        $detalles = $get('detalles') ?? [];
                                        $formasPago = $get('formas_pago') ?? [];

                                        $totalDetalles = collect($detalles)->sum('monto_cuota');
                                        $totalFormasPago = collect($formasPago)->sum('monto');
                                        $diferencia = $totalFormasPago - $totalDetalles;

                                        $icon = $diferencia == 0 ? '✅' : '⚠️';

                                        return $icon . ' Gs. ' . number_format($diferencia, 0, ',', '.');
                                    }),
                            ]),
                    ]),

                // Observaciones
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calcularTotales(Get $get, callable $set): void
    {
        $detalles = $get('../../detalles') ?? [];
        $formasPago = $get('../../formas_pago') ?? [];

        $totalDetalles = collect($detalles)->sum('monto_cuota');
        $totalFormasPago = collect($formasPago)->sum('monto');

        $set('../../monto_total', $totalDetalles);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_cobro')
                    ->label('N° Cobro')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_cobro')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('monto_total')
                    ->label('Monto Total')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),

                Tables\Columns\TextColumn::make('aperturaCaja.cod_apertura')
                    ->label('Apertura')
                    ->sortable(),

                Tables\Columns\TextColumn::make('detalles_count')
                    ->label('Facturas')
                    ->counts('detalles')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('formasPago_count')
                    ->label('Formas Pago')
                    ->counts('formasPago')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('fecha_cobro', 'desc');
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
            'index' => Pages\ListCobros::route('/'),
            'create' => Pages\CreateCobro::route('/create'),
            'view' => Pages\ViewCobro::route('/{record}'),
        ];
    }
}
