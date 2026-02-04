<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Articulos;
use Filament\Tables\Table;
use App\Models\PedidoCabecera;
use Filament\Resources\Resource;
use App\Models\PresupuestoCabecera;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Factories\Relationship;
use App\Filament\Resources\PresupuestoCabeceraResource\Pages;
use App\Filament\Resources\PresupuestoCabeceraResource\RelationManagers;
use App\Models\PedidoCabeceras;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class PresupuestoCabeceraResource extends Resource
{
    protected static ?string $model = PresupuestoCabecera::class;


    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Presupuesto Compra';

    protected static ?string $title = 'Presupuesto Compra';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';


public static function form(Form $form): Form
    {
        return $form->schema([
            // PRIMERA SECCIÓN: CABECERA
            Forms\Components\Section::make('Información de Cabecera')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                       /*     Forms\Components\TextInput::make('nro_presupuesto')
                                ->label('Número de Presupuesto')
                                ->placeholder('Se generará automáticame nte')
                                ->readOnly()
                                ->dehydrated(false),*/

                            // Si el nombre visible está en proveedor->personas_pro

                             Forms\Components\Select::make('cod_sucursal')
                                ->label('Sucursal')
                                ->relationship('sucursal', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn ($context) => $context === 'view' || $context === 'edit'),

                 Placeholder::make('usuario_display')
                        ->label('Usuario Alta')
                        ->content(function ($record) {
                            if ($record && $record->usuario_alta) {
                                return $record->usuario_alta;
                            }
                            $currentUser = auth()->user();
                            return $currentUser->username ?? $currentUser->name ?? $currentUser->email ?? 'N/A';
                        }),

                        Placeholder::make('fec_alta_display') // Dale un nombre único que no sea de una columna
                        ->label('Fecha Alta')
                        ->content(function () {
                            // Formateamos la fecha actual de Paraguay como un string
                            return Carbon::now('America/Asuncion')->format('d/m/Y');
                            }),

                            Forms\Components\Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor','id')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombres ?? $record?->nombres ?? ''
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\DatePicker::make('fec_presupuesto')
                                ->label('Fecha del Presupuesto')

                                ->required(),

                            Forms\Components\Select::make('cod_condicion_compra')
                                ->label('Condición de Compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required(),




                            //pone como default PENDIENTE
                            Forms\Components\Hidden::make('estado')
                                ->default('PENDIENTE')  // ← Estado PENDIENTE como string
                                ->dehydrated(true)      // lo envía al guardar
                                ->visibleOn('create'),  // solo en crear

                                // ... dentro del schema de Cabecera (Grid de 3 columnas) agrega:
                        Forms\Components\Select::make('nro_pedido_ref')
                        ->label('Pedido de Referencia')
                        ->options(function () {
                            // Solo pedidos APROBADOS que NO estén cargados en ningún presupuesto
                            $pedidosYaCargados = PresupuestoCabecera::whereNotNull('nro_pedido_ref')
                                ->pluck('nro_pedido_ref')
                                ->toArray();

                            return PedidoCabeceras::where('estado', 'APROBADO')
                                ->whereNotIn('cod_pedido', $pedidosYaCargados)
                                ->get()
                                ->mapWithKeys(function ($pedido) {
                                    $label = $pedido->cod_pedido;
                                    if ($pedido->fec_pedido) {
                                        $fecha = $pedido->fec_pedido instanceof \Carbon\Carbon
                                            ? $pedido->fec_pedido->format('d/m/Y')
                                            : $pedido->fec_pedido;
                                        $label .= ' — ' . $fecha;
                                    }
                                    return [$pedido->cod_pedido => $label];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            if (!$state) return;
                            static::cargarDetallesDesdePedido($state, $set, $get);
                        })
                        ->disabled(fn ($context) => $context === 'edit'), // Deshabilitar al editar


                        Textarea::make('observacion')
                            ->label('Observación')
                            ->maxLength(500)
                            ->columnSpan(3),

                    /*    Forms\Components\Select::make('cod_estado')
                        ->label('Estado')
                        ->relationship('estadoRel', 'descripcion') // define belongsTo en tu modelo
                        ->searchable()
                        ->preload()
                        ->required(),*/



                            // Estos los seteamos SOLO en Create vía hook (ver sección 2)
                            Forms\Components\Hidden::make('cod_sucursal')->visibleOn('create'),
                            Forms\Components\Hidden::make('usuario_alta')->visibleOn('create'),
                            Forms\Components\Hidden::make('fec_alta')->visibleOn('create'),
                            Forms\Components\Hidden::make('cargado')->default('N')->visibleOn('create'),
                        ]),
                ]),

            // SEGUNDA SECCIÓN: DETALLES
            Forms\Components\Section::make('Detalles del Presupuesto')
                ->schema([
                    TableRepeater::make('presupuestoDetalles')
                        // QUITAMOS ->relationship() para manejar el guardado manualmente
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('cod_articulo')
                                ->label('Artículo')
                                ->options(\App\Models\Articulos::pluck('descripcion', 'cod_articulo'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if ($state) {
                                        $articulo = Articulos::find($state);
                                        if ($articulo) {
                                            $set('precio', round($articulo->precio));
                                            // Calcular totales
                                            $cantidad = (float) ($get('cantidad') ?? 1);
                                            $precio = round($articulo->precio);
                                            $total = round($cantidad * $precio);
                                            $iva = round($total * 0.10);
                                            $set('total', $total);
                                            $set('total_iva', $iva);
                                        }
                                    }
                                }),

                            Forms\Components\TextInput::make('precio')
                                ->label('Precio')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->step(1)
                                ->suffix('₲')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) ($get('cantidad') ?? 1);
                                    $precio = (float) ($state ?? 0);
                                    $total = round($cantidad * $precio);
                                    $iva = round($total * 0.10);
                                    $set('total', $total);
                                    $set('total_iva', $iva);
                                }),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->minValue(0.01)
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) ($state ?? 1);
                                    $precio = (float) ($get('precio') ?? 0);
                                    $total = round($cantidad * $precio);
                                    $iva = round($total * 0.10);
                                    $set('total', $total);
                                    $set('total_iva', $iva);
                                }),

                            Forms\Components\TextInput::make('total')
                                ->label('Total')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲'),

                            Forms\Components\TextInput::make('total_iva')
                                ->label('IVA 10%')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲'),
                        ])
                        ->addActionLabel('+ Agregar Artículo')
                        ->defaultItems(0)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $detalles = $get('presupuestoDetalles') ?? [];
                            $grav = 0; $iva = 0;
                            foreach ($detalles as $d) {
                                $grav += (float) ($d['total'] ?? 0);
                                $iva  += (float) ($d['total_iva'] ?? 0);
                            }
                            $set('monto_gravado', round($grav));
                            $set('monto_tot_impuesto', round($iva));
                            $set('monto_general', round($grav + $iva));
                        })
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Get $get, Set $set) => (function () use ($get, $set) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $grav = 0; $iva = 0;
                                    foreach ($detalles as $d) {
                                        $grav += (float) ($d['total'] ?? 0);
                                        $iva  += (float) ($d['total_iva'] ?? 0);
                                    }
                                    $set('monto_gravado', round($grav));
                                    $set('monto_tot_impuesto', round($iva));
                                    $set('monto_general', round($grav + $iva));
                                })()
                            ),
                        ),
                ]),

            // TERCERA SECCIÓN: TOTALES
            Forms\Components\Section::make('Totales')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('monto_gravado')
                                ->label('Total Gravada')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $total = 0;
                                    foreach ($detalles as $d) {
                                        $total += (float) ($d['total'] ?? 0);
                                    }
                                    $set('monto_gravado', round($total));
                                }),

                            Forms\Components\TextInput::make('monto_tot_impuesto')
                                ->label('Total IVA')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $total = 0;
                                    foreach ($detalles as $d) {
                                        $total += (float) ($d['total_iva'] ?? 0);
                                    }
                                    $set('monto_tot_impuesto', round($total));
                                }),

                            Forms\Components\TextInput::make('monto_general')
                                ->label('Total General')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $gravada = 0;
                                    $iva = 0;
                                    foreach ($detalles as $d) {
                                        $gravada += (float) ($d['total'] ?? 0);
                                        $iva += (float) ($d['total_iva'] ?? 0);
                                    }
                                    $set('monto_general', round($gravada + $iva));
                                }),
                        ]),
                ]),
            ]);
    }


    protected static function cargarDetallesDesdePedido(int|string $codPedido, Set $set, Get $get): void
{
    $pedido = PedidoCabeceras::with(['detalles', 'detalles.articulo'])
        ->where('cod_pedido', $codPedido) // 👈 columna correcta
        ->first();

    if (!$pedido) {
        // Si querés limpiar cuando no existe:
        // $set('presupuestoDetalles', []);
        return;
    }

    $items = [];
    foreach ($pedido->detalles as $d) {
        $cantidad = (float) ($d->cantidad ?? 0);
        $precio   = (float) ($d->precio ?? 0);
        $exenta   = (float) ($d->exenta ?? 0);
        $total    = $cantidad * $precio;
        $iva      = max(0, ($total - $exenta)) * 0.10;

        $items[] = [
            'cod_articulo' => $d->cod_articulo,
            // si usás Hidden('descripcion') para el itemLabel del repeater:
            'descripcion'  => $d->articulo->descripcion ?? ($d->descripcion ?? ''),
            'precio'       => $precio,
             'cantidad'     => $cantidad,
            'Porc Impuesto' => 10,
            'Monto Impuesto',
           // 'exenta'       => $exenta,
            'total'        => number_format($total, 2, '.', ''),
            'total_iva'    => number_format($iva, 2, '.', ''),
        ];
    }

    // Reemplaza el contenido del Repeater por los ítems del pedido:
    $set('presupuestoDetalles', $items);

    // Recalcular totales de cabecera
    static::recalcularTotalesCabecera($get, $set);
}

protected static function recalcularTotalesCabecera(Get $get, Set $set): void
{
    $detalles = $get('presupuestoDetalles') ?? [];
    $grav = 0.0; $iva = 0.0;

    foreach ($detalles as $d) {
        $grav += (float) str_replace(',', '', $d['total'] ?? 0);
        $iva  += (float) str_replace(',', '', $d['total_iva'] ?? 0);
    }

    $set('monto_gravado', number_format($grav, 2, '.', ''));
    $set('monto_tot_impuesto', number_format($iva, 2, '.', ''));
    $set('monto_general', number_format($grav + $iva, 2, '.', ''));
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nro_presupuesto')
                    ->numeric()
                    ->label('Nro.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                 ->label('Proveedor')
                 ->searchable(),
                   // ->sortable(),
                Tables\Columns\TextColumn::make('fec_presupuesto')
                    ->date('d/m/Y'),
                    //->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                 ->label('Condición')
                 ->searchable()
                 ->sortable(),
                   // ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'PENDIENTE' => 'warning',
                    'APROBADO' => 'success',
                    'ANULADO' => 'danger',
                    default => 'gray',
                })
                ->sortable(),

            ])

        // Deshabilitar clic en las filas
        ->recordUrl(null)

        // 👇 Título de la columna de acciones
        ->actionsColumnLabel('Acciones')

            ->filters([
                //
            ])
            ->actions([
    ActionGroup::make([
        Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('info')
            ->icon('heroicon-m-eye')
            ->mutateRecordDataUsing(function (array $data, PresupuestoCabecera $record): array {
                // Cargar los detalles para el modal de ver
                $data['presupuestoDetalles'] = $record->presupuestoDetalles->map(function ($detalle) {
                    return [
                        'id_detalle' => $detalle->id_detalle,
                        'cod_articulo' => $detalle->cod_articulo,
                        'cantidad' => $detalle->cantidad,
                        'precio' => $detalle->precio,
                        'total' => $detalle->total,
                        'total_iva' => $detalle->total_iva,
                    ];
                })->toArray();
                return $data;
            }),

        Tables\Actions\EditAction::make()
            ->label('Editar')
            ->icon('heroicon-m-pencil-square'),

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PresupuestoCabecera $record) => $record->estado !== 'ANULADO')
            ->action(fn (PresupuestoCabecera $record) => $record->update(['estado' => 'ANULADO'])),

        Tables\Actions\Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (PresupuestoCabecera $record) => $record->estado === 'PENDIENTE')
            ->action(fn (PresupuestoCabecera $record) => $record->update(['estado' => 'APROBADO'])),
    ])
        ->label('Opciones')                      // texto del botón (opcional)
        ->icon('heroicon-m-ellipsis-vertical'),  // ícono de “tres puntitos”
    ]);



    }


    public static function getRelations(): array
    {
        return [
            //RelationManagers\PresupuestoDetallesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresupuestoCabeceras::route('/'),
            'create' => Pages\CreatePresupuestoCabecera::route('/create'),
            'edit' => Pages\EditPresupuestoCabecera::route('/{record}/edit'),
        ];
    }
}
