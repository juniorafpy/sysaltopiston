<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;
use App\Filament\Resources\NotaCreditoDebitoCompraResource\RelationManagers;
use App\Models\NotaCreditoDebitoCompra;
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
      protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Nota de Crédito/Débito';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Comprobante')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\Select::make('id_compra_cabecera')
                            ->label('Buscar Factura de Compra')
                            ->options(function () {
                                return \App\Models\CompraCabecera::with('proveedor')
                                    ->get()
                                    ->mapWithKeys(function ($compra) {
                                        $label = sprintf(
                                            '%s-%s-%s | %s | %s',
                                            $compra->tip_comprobante,
                                            $compra->ser_comprobante,
                                            $compra->nro_comprobante,
                                            $compra->proveedor->nombre ?? 'Sin proveedor',
                                            $compra->fec_comprobante->format('d/m/Y')
                                        );
                                        return [$compra->id_compra_cabecera => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->columnSpan(2)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (empty($state)) {
                                    $set('cod_proveedor', null);
                                    $set('proveedor_name', null);
                                    $set('detalles', []);
                                    return;
                                }

                                $compra = \App\Models\CompraCabecera::find($state);
                                if (!$compra) {
                                    $set('cod_proveedor', null);
                                    $set('proveedor_name', null);
                                    $set('detalles', []);
                                    return;
                                }

                                // Establecer proveedor desde la compra
                                $set('cod_proveedor', $compra->cod_proveedor);
                                $set('proveedor_name', $compra->proveedor ? $compra->proveedor->nombre : null);
                                $set('ser_comprobante', $compra->ser_comprobante);
                                $set('timbrado', $compra->timbrado);

                                // Cargar detalles de la compra
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
                            }),
                        Forms\Components\TextInput::make('proveedor_name')
                            ->label('Proveedor')
                            ->disabled()
                            ->columnSpan(2),
                        Forms\Components\Select::make('tip_comprobante')
                            ->label('Tipo de Nota')
                            ->options(['NC' => 'Nota de Crédito', 'ND' => 'Nota de Débito'])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('cod_motivo', null); // Limpiar motivo al cambiar tipo
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
                            ->reactive()
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
                        Forms\Components\Hidden::make('cod_proveedor')->required(),
                    ])
                ]),
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
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $precio = $get('precio_unitario') ?? 0;
                                    $iva = $get('porcentaje_iva') ?? 10;
                                    $subtotal = $state * $precio;
                                    $montoIva = $subtotal * ($iva / 100);
                                    $set('monto_total_linea', $subtotal + $montoIva);
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('precio_unitario')
                                ->label('Precio')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $cantidad = $get('cantidad') ?? 0;
                                    $iva = $get('porcentaje_iva') ?? 10;
                                    $subtotal = $cantidad * $state;
                                    $montoIva = $subtotal * ($iva / 100);
                                    $set('monto_total_linea', $subtotal + $montoIva);
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('porcentaje_iva')
                                ->label('IVA %')
                                ->numeric()
                                ->default(10)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $cantidad = $get('cantidad') ?? 0;
                                    $precio = $get('precio_unitario') ?? 0;
                                    $subtotal = $cantidad * $precio;
                                    $montoIva = $subtotal * ($state / 100);
                                    $set('monto_total_linea', $subtotal + $montoIva);
                                })
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('monto_total_linea')
                                ->label('Total')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1),
                        ])
                        ->columns(10)
                        ->default([])
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $totalGeneral = collect($state)->sum('monto_total_linea');
                            $set('total_general', $totalGeneral);
                        }),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
