<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;
use App\Filament\Resources\NotaCreditoDebitoCompraResource\RelationManagers;
use App\Models\NotaCreditoDebitoCompra;
use App\Models\CompraCabecera;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotaCreditoDebitoCompraResource extends Resource
{
    protected static ?string $model = NotaCreditoDebitoCompra::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
      protected static ?string $navigationGroup = 'Gestión Compras';
    protected static ?string $navigationLabel = 'Nota de Crédito/Débito';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Comprobante')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        // 1. Proveedor: campo principal que habilita el resto
                        Forms\Components\Select::make('cod_proveedor')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'cod_proveedor')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->personas_pro ? ($record->personas_pro->razon_social ?: trim($record->personas_pro->nombres . ' ' . $record->personas_pro->apellidos)) : $record->cod_proveedor;
                            })
                            ->searchable(['personas_pro.nro_documento', 'personas_pro.nombres', 'personas_pro.apellidos', 'personas_pro.razon_social'])
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Al cambiar proveedor, resetear factura y datos relacionados
                                $set('id_compra_cabecera', null);
                                $set('ser_comprobante', null);
                                $set('timbrado', null);
                                $set('nro_comprobante', null);
                                $set('fec_comprobante', now());
                                $set('detalles', []);
                                $set('total_general', 0);
                            }),

                        // 2. Factura de Compra: se habilita solo cuando hay proveedor seleccionado
                        Forms\Components\Select::make('id_compra_cabecera')
                            ->label('Buscar Factura de Compra')
                            ->options(function (callable $get) {
                                $codProveedor = $get('cod_proveedor');
                                if (!$codProveedor) {
                                    return [];
                                }

                                return CompraCabecera::where('cod_proveedor', $codProveedor)
                                    ->with('proveedor')
                                    ->get()
                                    ->mapWithKeys(function ($compra) {
                                        $label = sprintf(
                                            '%s-%s-%s | %s | %s',
                                            $compra->tip_comprobante,
                                            $compra->ser_comprobante,
                                            $compra->nro_comprobante,
                                            $compra->proveedor->nombre ?? 'Sin proveedor',
                                            $compra->fec_comprobante?->format('d/m/Y')
                                        );
                                        return [$compra->id_compra_cabecera => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(2)
                            ->disabled(fn (callable $get) => !$get('cod_proveedor'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (empty($state)) {
                                    $set('ser_comprobante', null);
                                    $set('timbrado', null);
                                    $set('nro_comprobante', null);
                                    $set('fec_comprobante', now());
                                    $set('detalles', []);
                                    $set('total_general', 0);
                                    return;
                                }

                                $compra = CompraCabecera::find($state);
                                if (!$compra) {
                                    $set('ser_comprobante', null);
                                    $set('timbrado', null);
                                    $set('nro_comprobante', null);
                                    $set('fec_comprobante', now());
                                    $set('detalles', []);
                                    $set('total_general', 0);
                                    return;
                                }

                                // Prellenar campos del comprobante desde la factura
                                $set('ser_comprobante', $compra->ser_comprobante);
                                $set('timbrado', $compra->timbrado);
                                $set('nro_comprobante', $compra->nro_comprobante);
                                $set('fec_comprobante', $compra->fec_comprobante);

                                // Cargar detalles de la compra como base para la nota
                                $detalles = $compra->detalles->map(function ($detalle) {
                                    return [
                                        'cod_articulo' => $detalle->cod_articulo,
                                        'cantidad' => $detalle->cantidad,
                                        'precio_unitario' => $detalle->precio_unitario,
                                        'porcentaje_iva' => $detalle->porcentaje_iva,
                                        'monto_total_linea' => $detalle->monto_total_linea,
                                    ];
                                })->toArray();

                                $set('detalles', $detalles);
                                $set('total_general', collect($detalles)->sum('monto_total_linea'));
                            }),

                        // 3. Tipo de Nota y Motivo
                        Forms\Components\Select::make('tip_comprobante')
                            ->label('Tipo de Nota')
                            ->options(['NC' => 'Nota de Crédito', 'ND' => 'Nota de Débito'])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('cod_motivo', null);
                            }),

                        Forms\Components\Select::make('cod_motivo')
                            ->label('Motivo')
                            ->options(function (callable $get) {
                                $tipoNota = $get('tip_comprobante');
                                if (!$tipoNota) {
                                    return [];
                                }
                                return \App\Models\MotivoNotaCreditoDebito::activos()
                                    ->where('tipo_nota', $tipoNota)
                                    ->pluck('descripcion', 'cod_motivo')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->helperText(function (callable $get) {
                                $motivoId = $get('cod_motivo');
                                if (!$motivoId) {
                                    return null;
                                }
                                $motivo = \App\Models\MotivoNotaCreditoDebito::find($motivoId);
                                if (!$motivo) {
                                    return null;
                                }
                                $textos = [];
                                if ($motivo->afecta_stock) {
                                    $textos[] = '📦 Afecta inventario';
                                }
                                if ($motivo->afecta_saldo) {
                                    $textos[] = '💰 Afecta saldo';
                                }
                                return implode(' | ', $textos);
                            })
                            ->columnSpan(2),

                        // 4. Datos del Comprobante de la Nota (campos manuales del proveedor)
                        Forms\Components\TextInput::make('ser_comprobante')
                            ->label('Serie')
                            ->required()
                            ->maxLength(10),

                        Forms\Components\TextInput::make('timbrado')
                            ->label('Timbrado')
                            ->required()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('nro_comprobante')
                            ->label('Nro. Comprobante')
                            ->required()
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('fec_comprobante')
                            ->label('Fecha')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('observacion')
                            ->label('Motivo o Descripción')
                            ->rows(2)
                            ->columnSpan(3),
                    ])
                ]),

            // 5. Detalle de la Nota (Repeater con cálculo reactivo)
            Forms\Components\Section::make('Detalle de la Nota')
                ->schema([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('Artículos')
                        ->schema([
                            Forms\Components\Select::make('cod_articulo')
                                ->label('Artículo')
                                ->relationship('articulo', 'descripcion')
                                ->searchable()
                                ->required()
                                ->columnSpan(4),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $precio = (float) ($get('precio_unitario') ?? 0);
                                    $iva = (float) ($get('porcentaje_iva') ?? 10);
                                    $subtotal = $state * $precio;
                                    $montoIva = $subtotal * ($iva / 100);
                                    $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                    
                                    $detalles = $get('../../detalles') ?? [];
                                    $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                    $set('../../total_general', round($totalGeneral, 0));
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('precio_unitario')
                                ->label('Precio')
                                ->numeric()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $cantidad = (float) ($get('cantidad') ?? 0);
                                    $iva = (float) ($get('porcentaje_iva') ?? 10);
                                    $subtotal = $cantidad * $state;
                                    $montoIva = $subtotal * ($iva / 100);
                                    $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                    
                                    $detalles = $get('../../detalles') ?? [];
                                    $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                    $set('../../total_general', round($totalGeneral, 0));
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('porcentaje_iva')
                                ->label('IVA %')
                                ->numeric()
                                ->default(10)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $cantidad = (float) ($get('cantidad') ?? 0);
                                    $precio = (float) ($get('precio_unitario') ?? 0);
                                    $subtotal = $cantidad * $precio;
                                    $montoIva = $subtotal * ($state / 100);
                                    $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                    
                                    $detalles = $get('../../detalles') ?? [];
                                    $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                    $set('../../total_general', round($totalGeneral, 0));
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('monto_total_linea')
                                ->label('Total')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('Gs.')
                                ->columnSpan(1),
                        ])
                        ->columns(10)
                        ->default([])
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $totalGeneral = collect($state)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                            $set('total_general', round($totalGeneral, 0));
                        })
                        ->deletable()
                        ->reorderable(),

                    Forms\Components\Grid::make(5)->schema([
                        Forms\Components\Placeholder::make('empty')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('total_general')
                            ->label('Total General')
                            ->disabled()
                            ->prefix('Gs.')
                            ->extraInputAttributes(['class' => 'text-lg text-primary-500 font-bold'])
                            ->columnSpan(1),
                    ])
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_completo')
                    ->label('Nro. Comprobante')
                    ->searchable(['nro_comprobante', 'ser_comprobante'])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tip_comprobante')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'NC',
                        'warning' => 'ND',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'NC' ? 'Nota Crédito' : 'Nota Débito'),
                Tables\Columns\TextColumn::make('fec_comprobante')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('compraCabecera.numero_completo')
                    ->label('Factura Origen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motivo.descripcion')
                    ->label('Motivo')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->motivo?->afecta_stock ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('total_nota')
                    ->label('Total')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tip_comprobante')
                    ->label('Tipo')
                    ->options([
                        'NC' => 'Nota de Crédito',
                        'ND' => 'Nota de Débito',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('fec_comprobante', 'desc');
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
            'index' => Pages\ListNotaCreditoDebitoCompras::route('/'),
            'create' => Pages\CreateNotaCreditoDebitoCompra::route('/create'),
            'edit' => Pages\EditNotaCreditoDebitoCompra::route('/{record}/edit'),
        ];
    }
}
