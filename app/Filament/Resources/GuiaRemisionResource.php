<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Almacen;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CompraCabecera;
use Filament\Resources\Resource;
use App\Models\GuiaRemisionCabecera;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\NumericInput;
use App\Filament\Resources\GuiaRemisionResource\Pages;

class GuiaRemisionResource extends Resource
{
    protected static ?string $model = GuiaRemisionCabecera::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Nota de Remisión';

    protected static ?int $navigationSort = 5;



    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([ // Columna Izquierda
                Section::make('Detalles de la Factura de Compra')
                    ->schema([
                        Select::make('compra_cabecera_id')
                            ->label('Factura de Compra')
                            ->relationship('compraCabecera', 'nro_comprobante')
                            ->getOptionLabelFromRecordUsing(fn(CompraCabecera $record) => "Factura N°: {$record->nro_comprobante} | Proveedor: {$record->proveedor->nombre} | Fecha: " . date('d/m/Y', strtotime($record->fec_comprobante)))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('proveedor_ruc', null);
                                    $set('proveedor_nombre', null);
                                    $set('detalles', []);
                                    return;
                                }
                                $compra = CompraCabecera::with('proveedor', 'detalles.articulo')->find($state);
                                if ($compra) {
                                    $set('proveedor_ruc', $compra->proveedor->ruc);
                                    $set('proveedor_nombre', $compra->proveedor->nombre);
                                    $items = $compra->detalles->map(fn($detalle) => [
                                        'articulo_id' => $detalle->articulo_id,
                                        'articulo_nombre' => $detalle->articulo->descripcion,
                                        'cantidad_facturada' => $detalle->cantidad,
                                        'cantidad_recibida' => $detalle->cantidad,
                                    ])->toArray();
                                    $set('detalles', $items);
                                }
                            })
                            ->required(),

                        Grid::make(3)->schema([
                            TextInput::make('tipo_comprobante')
                                ->label('Tipo')
                                ->default('REM')
                                ->disabled()
                                ->required(),
                            TextInput::make('ser_remision')
                                ->label('Serie')
                                ->default('A')
                                ->disabled()
                                ->required(),
                            TextInput::make('numero_remision')
                                ->label('Número de Remisión')
                                ->required(),
                        ]),
                        DatePicker::make('fecha_remision')->label('Fecha de Remisión')->default(now())->required(),
                        Select::make('almacen_id')->label('Almacén de Destino')->options(Almacen::all()->pluck('nombre', 'id'))->searchable()->required(),

                        Fieldset::make('Datos del Proveedor')->schema([
                            TextInput::make('proveedor_ruc')->label('RUC/ID')->disabled(),
                            TextInput::make('proveedor_nombre')->label('Nombre o Razón Social')->disabled(),
                        ])->visible(fn(Get $get) => $get('compra_cabecera_id'))->columnSpanFull(),
                    ])
                    ->columns(2),
            ])->columnSpan(['lg' => 2]),

     //       ])->columnSpan(['lg' => 2]),


                 Group::make()->schema([ // Columna Derecha
                   /* Section::make('Datos de la Remisión')->schema([
                        TextInput::make('numero_remision')->label('Número de Remisión')->required(),
                        DatePicker::make('fecha_remision')->label('Fecha de Remisión')->default(now())->required(),
                        Select::make('almacen_id')->label('Almacén de Destino')->options(Almacen::all()->pluck('nombre', 'id'))->searchable()->required(),
                ]),*/


                Section::make('Información del Sistema')->schema([
                    Hidden::make('cod_empleado')->dehydrated(),
                    TextInput::make('usuario_alta')
                        ->label('Empleado')
                        ->disabled()
                        ->dehydrated(false),

                    Hidden::make('cod_sucursal')->dehydrated(),
                    TextInput::make('nombre_sucursal')
                        ->label('Sucursal')
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('fec_alta')
                        ->label('Fecha Alta')
                        ->content(fn () => now()->format('d/m/Y H:i')),
                ]),
            ])->columnSpan(['lg' => 1]),

            Section::make('Ítems a Recibir')->schema([
                Repeater::make('detalles')
                    ->label('')
                    ->schema([
                        TextInput::make('articulo_nombre')->label('Artículo')->disabled()->columnSpan(2),
                        TextInput::make('cantidad_facturada')->label('Cant. Facturada')->disabled(),
                        TextInput::make('cantidad_recibida')
                            ->label('Cant. Recibida')
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn(Get $get) => $get('cantidad_facturada'))
                            ->helperText(fn(Get $get) => 'Pendiente de recibir: ' . $get('cantidad_facturada') . ' unidades.'),
                        Hidden::make('articulo_id'),
                    ])
                    ->columns(4)
                    ->reorderable(false)
                    ->addable(false)
                    ->deletable(false)
                    ->visible(fn(Get $get) => !empty($get('detalles'))),
            ])->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_remision')->label('N° Remisión')->searchable(),
                Tables\Columns\TextColumn::make('compraCabecera.nro_comprobante')->label('N° Factura Compra')->searchable(),
                Tables\Columns\TextColumn::make('compraCabecera.proveedor.nombre')->label('Proveedor')->searchable(),
                Tables\Columns\TextColumn::make('almacen.nombre')->label('Almacén Destino'),
                Tables\Columns\TextColumn::make('fecha_remision')->date('d/m/Y'),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuiasRemision::route('/'),
            'create' => Pages\CreateGuiaRemision::route('/create'),
            'edit' => Pages\EditGuiaRemision::route('/{record}/edit'),
        ];
    }
}
