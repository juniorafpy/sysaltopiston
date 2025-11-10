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
                            ->relationship('compraCabecera', 'nro_comprobante')
                            ->searchable()
                            ->reactive()
                            ->columnSpan(2)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (empty($state)) {
                                    $set('proveedor_id', null);
                                    $set('proveedor_name', null);
                                    $set('detalles', []);
                                    return;
                                }

                                $compra = \App\Models\CompraCabecera::find($state);
                                if (!$compra || !$compra->nro_oc_ref) {
                                    $set('proveedor_id', null);
                                    $set('proveedor_name', null);
                                    $set('detalles', []);
                                    return;
                                }

                                $set('proveedor_id', $compra->cod_proveedor);
                                $set('proveedor_name', $compra->proveedor ? $compra->proveedor->nombre : null);

                                $ordenCompra = \App\Models\OrdenCompraCabecera::where('nro_orden_compra', $compra->nro_oc_ref)->first();
                                if (!$ordenCompra) {
                                    return;
                                }

                                $detalles = $ordenCompra->ordenCompraDetalles->map(function ($detalle) {
                                    return [
                                        'articulo_id' => $detalle->cod_articulo,
                                        'cantidad' => $detalle->cantidad,
                                        'precio_unitario' => $detalle->precio,
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
                            ->options(['NC' => 'Crédito', 'ND' => 'Débito'])
                            ->required(),
                        Forms\Components\DatePicker::make('fec_comprobante')
                            ->label('Fecha')->default(now())->required(),
                        Forms\Components\Textarea::make('observacion')
                            ->label('Motivo o Descripción')->rows(2)
                            ->columnSpan(2),
                        Forms\Components\Hidden::make('proveedor_id')->required(),
                    ])
                ]),
            Forms\Components\Section::make('Detalle de la Nota')
                ->schema([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('Artículos')
                        ->schema([
                            Forms\Components\Select::make('articulo_id')
                                ->label('Artículo')
                                ->relationship('articulo', 'descripcion')
                                ->disabled()
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('cantidad')
                                ->numeric()->required()->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('subtotal', $state * $get('precio_unitario')))
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('precio_unitario')
                                ->label('Precio')->numeric()->required()->reactive()
                                ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('subtotal', $state * $get('cantidad')))
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')->numeric()->disabled()
                                ->columnSpan(2),
                        ])
                        ->columns(10)
                        ->default([])
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $totalGeneral = collect($state)->sum(function($row) {
                                return ($row['cantidad'] ?? 0) * ($row['precio_unitario'] ?? 0);
                            });
                            $set('total_general', $totalGeneral);
                        }),
                    Forms\Components\Grid::make(5)->schema([
                        Forms\Components\Placeholder::make('empty')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('total_general')
                            ->label('Total General')
                            ->disabled()->prefix('Gs.')
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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
