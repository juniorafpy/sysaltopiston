<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Almacen;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use App\Models\CompraCabecera;
use Filament\Resources\Resource;
use App\Models\GuiaRemisionCabecera;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\GuiaRemisionResource\Pages;

class GuiaRemisionResource extends Resource
{
    protected static ?string $model = GuiaRemisionCabecera::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Nota de Remisión';

    protected static ?int $navigationSort = 5;

    // Cargar relaciones necesarias para la vista
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'compraCabecera.proveedor.personas_pro',
                'sucursal',
                'detalles.articulo'
            ]);
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([ // Columna Izquierda
                Section::make('Detalles de la Factura de Compra')
                    ->schema([
                        Select::make('compra_cabecera_id')
                            ->label('Factura de Compra')
                            ->options(function () {
                                return CompraCabecera::with(['proveedor.personas_pro', 'detalles'])
                                    ->get()
                                    ->filter(function ($compra) {
                                        // Solo mostrar facturas con items pendientes de recepcionar
                                        return !$compra->esta_completamente_recepcionada;
                                    })
                                    ->mapWithKeys(function ($compra) {
                                        $proveedor = $compra->proveedor?->personas_pro?->nombre_completo
                                            ?? $compra->proveedor?->nombre
                                            ?? 'Sin proveedor';
                                        $fecha = $compra->fec_comprobante?->format('d/m/Y') ?? 'Sin fecha';
                                        $serie = $compra->ser_comprobante ?? '';
                                        $numero = $compra->nro_comprobante ?? '';
                                        $porcentaje = $compra->porcentaje_recepcion;

                                        $estado = $porcentaje > 0 ? "[{$porcentaje}% recibido]" : '[Pendiente]';
                                        $label = "Factura {$serie}-{$numero} | {$proveedor} | {$fecha} {$estado}";

                                        return [$compra->id_compra_cabecera => $label];
                                    });
                            })
                            ->helperText('Solo se muestran facturas con artículos pendientes de recepcionar')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('proveedor_ruc', null);
                                    $set('proveedor_nombre', null);
                                    $set('cod_sucursal', null);
                                    $set('almacen_id', null);
                                    $set('detalles', []);
                                    return;
                                }
                                $compra = CompraCabecera::with('proveedor.personas_pro', 'detalles.articulo', 'sucursal')->find($state);
                                if ($compra) {
                                    $proveedorNombre = $compra->proveedor?->personas_pro?->nombre_completo
                                        ?? $compra->proveedor?->nombre
                                        ?? 'Sin proveedor';
                                    $proveedorRuc = $compra->proveedor?->personas_pro?->documento_nro
                                        ?? $compra->proveedor?->personas_pro?->ruc
                                        ?? 'Sin RUC';

                                    $set('proveedor_ruc', $proveedorRuc);
                                    $set('proveedor_nombre', $proveedorNombre);

                                    // Establecer la sucursal de la factura
                                    $set('cod_sucursal', $compra->cod_sucursal);

                                    // Establecer almacen_id = cod_sucursal de la factura
                                    $set('almacen_id', $compra->cod_sucursal);

                                    // Calcular cantidades pendientes por artículo
                                    $items = $compra->detalles
                                        ->filter(fn($detalle) => $detalle->cantidad_pendiente > 0) // Solo artículos con pendientes
                                        ->map(fn($detalle) => [
                                            'articulo_id' => $detalle->cod_articulo,
                                            'articulo_nombre' => $detalle->articulo->descripcion ?? 'Sin descripción',
                                            'cantidad_facturada' => $detalle->cantidad,
                                            'cantidad_ya_recibida' => $detalle->cantidad_recibida,
                                            'cantidad_pendiente' => $detalle->cantidad_pendiente,
                                            'cantidad_recibida' => $detalle->cantidad_pendiente, // Por defecto, recepcionar lo pendiente
                                        ])->values()->toArray();
                                    $set('detalles', $items);
                                }
                            })
                            ->required(),

                        Grid::make(3)->schema([
                            TextInput::make('tipo_comprobante')
                                ->label('Tipo')
                                ->default('REM')
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            TextInput::make('ser_remision')
                                ->label('Serie')
                                ->default('001-001')
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            TextInput::make('numero_remision')
                                ->label('Número de Remisión')
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->helperText('Se genera automáticamente'),
                        ]),
                        DatePicker::make('fecha_remision')
                            ->label('Fecha de Remisión')
                            ->default(now())
                            ->required(),

                        Select::make('almacen_id')
                            ->label('Depósito Destino (Sucursal)')
                            ->relationship('sucursal', 'descripcion', function ($query, $get) {
                                // Mostrar solo la sucursal de la factura seleccionada
                                if ($sucursalId = $get('cod_sucursal')) {
                                    return $query->where('cod_sucursal', $sucursalId);
                                }
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->helperText('Se establece automáticamente desde la sucursal de la factura'),

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
                    Select::make('cod_sucursal')
                        ->label('Sucursal (desde factura)')
                        ->relationship('sucursal', 'descripcion')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Se establece automáticamente desde la factura seleccionada'),

                    TextInput::make('cod_empleado')
                        ->label('Usuario')
                        ->default(fn () => auth()->user()->name ?? 'Sistema')
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
                        TextInput::make('articulo_nombre')
                            ->label('Artículo')
                            ->disabled()
                            ->columnSpan(2),
                        TextInput::make('cantidad_facturada')
                            ->label('Cant. Facturada')
                            ->disabled()
                            ->suffix('unid.'),
                        TextInput::make('cantidad_ya_recibida')
                            ->label('Ya Recibida')
                            ->disabled()
                            ->suffix('unid.')
                            ->default(0),
                        TextInput::make('cantidad_pendiente')
                            ->label('Pendiente')
                            ->disabled()
                            ->suffix('unid.'),
                        TextInput::make('cantidad_recibida')
                            ->label('A Recepcionar Ahora')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn(Get $get) => $get('cantidad_pendiente'))
                            ->suffix('unid.')
                            ->helperText(fn(Get $get) => 'Máximo: ' . $get('cantidad_pendiente') . ' unidades'),
                        Hidden::make('articulo_id'),
                    ])
                    ->columns(6)
                    ->reorderable(false)
                    ->addable(false)
                    ->deletable(false)
                    ->visible(fn(Get $get) => !empty($get('detalles'))),
            ])->columnSpanFull(),
        ])->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Información de la Nota de Remisión')
                    ->schema([
                        TextEntry::make('numero_remision')
                            ->label('Número de Remisión')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('fecha_remision')
                            ->label('Fecha de Remisión')
                            ->date('d/m/Y'),
                        TextEntry::make('tipo_comprobante')
                            ->label('Tipo de Comprobante'),
                        TextEntry::make('ser_remision')
                            ->label('Serie'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'P' => 'warning',
                                'A' => 'success',
                                'N' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'P' => 'Pendiente',
                                'A' => 'Aprobado',
                                'N' => 'Anulado',
                                default => $state,
                            }),
                    ])
                    ->columns(3),

                InfoSection::make('Datos de la Factura de Compra')
                    ->schema([
                        TextEntry::make('compraCabecera.nro_comprobante')
                            ->label('N° de Factura')
                            ->formatStateUsing(fn ($state, $record) =>
                                ($record->compraCabecera->ser_comprobante ?? '') . '-' . $state
                            ),
                        TextEntry::make('compraCabecera.fec_comprobante')
                            ->label('Fecha de Factura')
                            ->date('d/m/Y'),
                        TextEntry::make('compraCabecera.estado_recepcion')
                            ->label('Estado de Recepción')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'RECEPCIONADO' => 'success',
                                'PARCIAL' => 'warning',
                                'PENDIENTE' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                InfoSection::make('Datos del Proveedor')
                    ->schema([
                        TextEntry::make('compraCabecera.proveedor.personas_pro.nombre_completo')
                            ->label('Nombre o Razón Social')
                            ->default(fn ($record) =>
                                $record->compraCabecera?->proveedor?->nombre ?? 'Sin proveedor'
                            ),
                        TextEntry::make('compraCabecera.proveedor.personas_pro.documento_nro')
                            ->label('RUC/Documento')
                            ->default(fn ($record) =>
                                $record->compraCabecera?->proveedor?->personas_pro?->ruc ??
                                $record->compraCabecera?->proveedor?->personas_pro?->documento_nro ??
                                'Sin documento'
                            ),
                    ])
                    ->columns(2),

                InfoSection::make('Depósito y Usuario')
                    ->schema([
                        TextEntry::make('sucursal.descripcion')
                            ->label('Depósito Destino (Sucursal)'),
                        TextEntry::make('usuario_alta')
                            ->label('Usuario de Carga')
                            ->default('Sin usuario'),
                        TextEntry::make('fec_alta')
                            ->label('Fecha de Carga')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),

                InfoSection::make('Detalles de Artículos Recibidos')
                    ->schema([
                        RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                TextEntry::make('articulo.descripcion')
                                    ->label('Artículo'),
                                TextEntry::make('articulo.codigo')
                                    ->label('Código'),
                                TextEntry::make('cantidad_recibida')
                                    ->label('Cantidad Recibida')
                                    ->badge()
                                    ->color('success'),
                            ])
                            ->columns(3)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_remision')
                    ->label('N° Remisión')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('compraCabecera.nro_comprobante')
                    ->label('N° Factura')
                    ->searchable(),

                Tables\Columns\TextColumn::make('compraCabecera.proveedor.personas_pro.nombre_completo')
                    ->label('Proveedor')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('sucursal.descripcion')
                    ->label('Sucursal Destino'),

                Tables\Columns\TextColumn::make('fecha_remision')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'P' => 'warning',
                        'A' => 'success',
                        'N' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'P' => 'Pendiente',
                        'A' => 'Aprobado',
                        'N' => 'Anulado',
                        default => $state,
                    }),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Nota de Remisión: ' . $record->numero_remision)
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular Nota de Remisión')
                        ->modalDescription('Esta acción revertirá el stock ingresado. ¿Está seguro?')
                        ->modalSubmitActionLabel('Sí, anular')
                        ->action(function (GuiaRemisionCabecera $record) {
                            DB::transaction(function () use ($record) {
                                // Reversar el stock
                                foreach ($record->detalles as $detalle) {
                                    $existencia = \App\Models\ExistenciaArticulo::where('cod_articulo', $detalle->articulo_id)
                                        ->where('cod_sucursal', $record->cod_sucursal)
                                        ->first();

                                    if ($existencia) {
                                        // Restar la cantidad que se había agregado
                                        $existencia->decrement('stock_actual', $detalle->cantidad_recibida);
                                        $existencia->update([
                                            'usuario_mod' => auth()->user()->name ?? 'Sistema',
                                            'fec_mod' => now(),
                                        ]);
                                    }
                                }

                                // Cambiar estado a Anulado
                                $record->update([
                                    'estado' => 'N',
                                    'usuario_mod' => auth()->user()->name ?? 'Sistema',
                                    'fec_mod' => now(),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Nota de Remisión anulada')
                                ->body('El stock ha sido revertido correctamente.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (GuiaRemisionCabecera $record) => $record->estado !== 'N'),
                ])
                ->tooltip('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ])
            ]);
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
