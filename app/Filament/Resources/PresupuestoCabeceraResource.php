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
use Illuminate\Database\Eloquent\Model;
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


     protected static ?string $navigationGroup = 'Gestión de Compra';
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
                                ->disabled()
                                ->dehydrated(true),

                                    Forms\Components\DatePicker::make('fec_presupuesto')
                                ->label('Fecha del Presupuesto')
                                ->default(Carbon::now('America/Asuncion')->toDateString())
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),

                            Forms\Components\Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor','id')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombres ?? $record?->nombres ?? ''
                                )
                                ->searchable()
                                ->preload()
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
                        ->options(function (?Model $record) {
                            // Solo pedidos APROBADOS que NO estén cargados en otro presupuesto
                            $pedidosYaCargados = PresupuestoCabecera::whereNotNull('nro_pedido_ref')
                                ->when($record, fn($q) => $q->where('nro_presupuesto', '!=', $record->nro_presupuesto))
                                ->pluck('nro_pedido_ref')
                                ->toArray();

                            return PedidoCabeceras::where('estado', 'APROBADO')
                                ->whereNotIn('cod_pedido', $pedidosYaCargados)
                                ->get()
                                ->mapWithKeys(function ($pedido) {
                                    $label = $pedido->cod_pedido;
                                    if ($pedido->fec_pedido) {
                                        $fecha = \Carbon\Carbon::parse($pedido->fec_pedido)->format('d/m/Y');
                                        $label .= ' — ' . $fecha;
                                    }
                                    return [$pedido->cod_pedido => $label];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get, string $operation) {
                            if (!$state || in_array($operation, ['view', 'edit'])) return;
                            static::cargarDetallesDesdePedido($state, $set, $get);
                        })
                        ->disabled(fn ($context) => in_array($context, ['edit', 'view'])),


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
                        ->label('')
                        ->colStyles([
                            'cod_articulo' => 'width: 280px; min-width: 280px;',
                            'precio'       => 'width: 170px; min-width: 170px;',
                            'cantidad'     => 'width: 90px;  min-width: 90px;',
                            'total'        => 'width: 170px; min-width: 170px;',
                            'total_iva'    => 'width: 170px; min-width: 170px;',
                        ])
                        ->schema([
                            Forms\Components\Select::make('cod_articulo')
                                ->label('Artículo')
                                ->options(\App\Models\Articulos::pluck('descripcion', 'cod_articulo'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (Get $get) => !blank($get('../../nro_pedido_ref')))
                                ->dehydrated(true)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if ($state) {
                                        $articulo = Articulos::find($state);
                                        if ($articulo) {
                                            $precio = round($articulo->precio);
                                            $cantidad = (int) str_replace('.', '', (string)($get('cantidad') ?? 1));
                                            $total = round($cantidad * $precio);
                                            $iva = round($total / 11);
                                            $set('precio', number_format($precio, 0, ',', '.'));
                                            $set('total', number_format($total, 0, ',', '.'));
                                            $set('total_iva', number_format($iva, 0, ',', '.'));
                                        }
                                    }
                                    $detalles = $get('../../presupuestoDetalles') ?? [];
                                    $gravTotal = 0; $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $gravTotal += ($t - $iv);
                                        $ivaTotal  += $iv;
                                    }
                                    $set('../../monto_gravado', number_format($gravTotal, 0, ',', '.'));
                                    $set('../../monto_tot_impuesto', number_format($ivaTotal, 0, ',', '.'));
                                    $set('../../monto_general', number_format($gravTotal + $ivaTotal, 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('precio')
                                ->label('Precio')
                                ->required()
                                ->suffix('₲')
                                ->formatStateUsing(fn ($state) => $state ? number_format((float) str_replace('.', '', (string)$state), 0, ',', '.') : '')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) str_replace('.', '', (string)($get('cantidad') ?? 1));
                                    $precio = (float) str_replace('.', '', (string)($state ?? 0));
                                    $total = round($cantidad * $precio);
                                    $iva = round($total / 11);
                                    $set('precio', number_format($precio, 0, ',', '.'));
                                    $set('total', number_format($total, 0, ',', '.'));
                                    $set('total_iva', number_format($iva, 0, ',', '.'));
                                    $detalles = $get('../../presupuestoDetalles') ?? [];
                                    $gravTotal = 0; $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $gravTotal += ($t - $iv);
                                        $ivaTotal  += $iv;
                                    }
                                    $set('../../monto_gravado', number_format($gravTotal, 0, ',', '.'));
                                    $set('../../monto_tot_impuesto', number_format($ivaTotal, 0, ',', '.'));
                                    $set('../../monto_general', number_format($gravTotal + $ivaTotal, 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->default(1)
                                ->required()
                                ->disabled(fn (Get $get) => !blank($get('../../nro_pedido_ref')))
                                ->dehydrated(true)
                                ->formatStateUsing(fn ($state) => $state !== null ? (int)$state : 1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) str_replace('.', '', (string)($state ?? 1));
                                    $precio = (float) str_replace('.', '', (string)($get('precio') ?? 0));
                                    $total = round($cantidad * $precio);
                                    $iva = round($total / 11);
                                    $set('total', number_format($total, 0, ',', '.'));
                                    $set('total_iva', number_format($iva, 0, ',', '.'));
                                    $detalles = $get('../../presupuestoDetalles') ?? [];
                                    $gravTotal = 0; $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $gravTotal += ($t - $iv);
                                        $ivaTotal  += $iv;
                                    }
                                    $set('../../monto_gravado', number_format($gravTotal, 0, ',', '.'));
                                    $set('../../monto_tot_impuesto', number_format($ivaTotal, 0, ',', '.'));
                                    $set('../../monto_general', number_format($gravTotal + $ivaTotal, 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('total')
                                ->label('Total')
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->formatStateUsing(fn ($state) => $state ? number_format((float) str_replace('.', '', (string)$state), 0, ',', '.') : ''),

                            Forms\Components\TextInput::make('total_iva')
                                ->label('IVA 10%')
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->formatStateUsing(fn ($state) => $state ? number_format((float) str_replace('.', '', (string)$state), 0, ',', '.') : ''),
                        ])
                        ->addActionLabel('+ Agregar Artículo')
                        ->defaultItems(0)
                        ->addable(fn (Get $get) => blank($get('nro_pedido_ref')))
                        ->deletable(fn (Get $get) => blank($get('nro_pedido_ref')))
                        ->reorderable(fn (Get $get) => blank($get('nro_pedido_ref')))
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $detalles = $get('presupuestoDetalles') ?? [];
                            $gravTotal = 0; $ivaTotal = 0;
                            foreach ($detalles as $d) {
                                $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                $gravTotal += ($t - $iv);
                                $ivaTotal  += $iv;
                            }
                            $set('monto_gravado', number_format($gravTotal, 0, ',', '.'));
                            $set('monto_tot_impuesto', number_format($ivaTotal, 0, ',', '.'));
                            $set('monto_general', number_format($gravTotal + $ivaTotal, 0, ',', '.'));
                        })
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Get $get, Set $set) => (function () use ($get, $set) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $gravTotal = 0; $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $gravTotal += ($t - $iv);
                                        $ivaTotal  += $iv;
                                    }
                                    $set('monto_gravado', number_format($gravTotal, 0, ',', '.'));
                                    $set('monto_tot_impuesto', number_format($ivaTotal, 0, ',', '.'));
                                    $set('monto_general', number_format($gravTotal + $ivaTotal, 0, ',', '.'));
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
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                    if ($state !== null && $state !== '') {
                                        $set('monto_gravado', number_format((float)$state, 0, ',', '.'));
                                        return;
                                    }
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $gravTotal = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (float)($d['total'] ?? 0);
                                        $iv = (float)($d['total_iva'] ?? 0);
                                        $gravTotal += ($t - $iv);
                                    }
                                    $set('monto_gravado', number_format(round($gravTotal), 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('monto_tot_impuesto')
                                ->label('Total IVA')
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                    if ($state !== null && $state !== '') {
                                        $set('monto_tot_impuesto', number_format((float)$state, 0, ',', '.'));
                                        return;
                                    }
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $ivaTotal += (float)($d['total_iva'] ?? 0);
                                    }
                                    $set('monto_tot_impuesto', number_format(round($ivaTotal), 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('monto_general')
                                ->label('Total General')
                                ->readOnly()
                                ->dehydrated(true)
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                    if ($state !== null && $state !== '') {
                                        $set('monto_general', number_format((float)$state, 0, ',', '.'));
                                        return;
                                    }
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $gravTotal = 0; $ivaTotal = 0;
                                    foreach ($detalles as $d) {
                                        $gravTotal += (float)($d['total'] ?? 0);
                                        $ivaTotal  += (float)($d['total_iva'] ?? 0);
                                    }
                                    $set('monto_general', number_format(round($gravTotal + $ivaTotal), 0, ',', '.'));
                                }),
                        ]),
                ]),

            // SECCIÓN AUDITORÍA
            Forms\Components\Section::make('Información de Auditoría')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Placeholder::make('usuario_display')
                                ->label('Usuario Alta')
                                ->content(function ($record) {
                                    if ($record && $record->usuario_alta) {
                                        return $record->usuario_alta;
                                    }
                                    $currentUser = auth()->user();
                                    return $currentUser->username ?? $currentUser->name ?? $currentUser->email ?? 'N/A';
                                }),

                            Placeholder::make('fec_alta_display')
                                ->label('Fecha Alta')
                                ->content(function () {
                                    return Carbon::now('America/Asuncion')->format('d/m/Y H:i');
                                }),
                        ]),
                ])
                ->collapsed()
                ->collapsible(),
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
        $total    = round($cantidad * $precio);
        $iva      = round($total / 11);

        $items[] = [
            'cod_articulo' => $d->cod_articulo,
            'descripcion'  => $d->articulo->descripcion ?? ($d->descripcion ?? ''),
            'precio'       => number_format($precio, 0, ',', '.'),
            'cantidad'     => $cantidad,
            'total'        => number_format($total, 0, ',', '.'),
            'total_iva'    => number_format($iva, 0, ',', '.'),
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
        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
        $grav += ($t - $iv);
        $iva  += $iv;
    }

    $set('monto_gravado', number_format(round($grav), 0, ',', '.'));
    $set('monto_tot_impuesto', number_format(round($iva), 0, ',', '.'));
    $set('monto_general', number_format(round($grav + $iva), 0, ',', '.'));
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

        // Ordenar por fecha más reciente por defecto
        ->defaultSort('fec_presupuesto', 'desc')

            ->filters([
                //
            ])
            ->actions([
    ActionGroup::make([
        Tables\Actions\Action::make('imprimir')
            ->label('Imprimir')
            ->icon('heroicon-m-printer')
            ->color('gray')
            ->url(fn (PresupuestoCabecera $record) => route('presupuestos.pdf', $record->nro_presupuesto))
            ->openUrlInNewTab(),

        Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('info')
            ->icon('heroicon-m-eye')
            ->modalWidth('7xl')
            ->modalHeading(fn (PresupuestoCabecera $record) => 'Vista Presupuesto N° ' . $record->nro_presupuesto)
            ->mutateRecordDataUsing(function (array $data, PresupuestoCabecera $record): array {
                $detalles = \App\Models\PresupuestoDetalle::where('nro_presupuesto', $record->nro_presupuesto)->get();
                $data['presupuestoDetalles'] = $detalles->map(function ($detalle) {
                    $precio   = (float)$detalle->precio;
                    $cantidad = (int)$detalle->cantidad;
                    $total    = round($cantidad * $precio);
                    $iva      = round($total / 11);
                    return [
                        'id_detalle'   => $detalle->id_detalle,
                        'cod_articulo' => $detalle->cod_articulo,
                        'cantidad'     => $cantidad,
                        'precio'       => number_format($precio, 0, ',', '.'),
                        'total'        => number_format($total, 0, ',', '.'),
                        'total_iva'    => number_format($iva, 0, ',', '.'),
                    ];
                })->toArray();
                return $data;
            }),

        Tables\Actions\EditAction::make()
            ->label('Editar')
            ->icon('heroicon-m-pencil-square')
            ->visible(fn (PresupuestoCabecera $record) => $record->estado === 'PENDIENTE'),

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (PresupuestoCabecera $record) => $record->estado !== 'ANULADO')
            ->action(function (PresupuestoCabecera $record) {
                // Liberar el pedido de referencia si existe
                $record->update([
                    'estado'         => 'ANULADO',
                    'nro_pedido_ref' => null,
                ]);
                
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Presupuesto Anulado')
                    ->body('El presupuesto ha sido anulado correctamente.')
                    ->send();
            }),

        Tables\Actions\Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (PresupuestoCabecera $record) => $record->estado === 'PENDIENTE')
            ->action(function (PresupuestoCabecera $record) {
                $record->update(['estado' => 'APROBADO']);
                
                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('Presupuesto Aprobado')
                    ->body('El presupuesto ha sido aprobado exitosamente.')
                    ->send();
            }),
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
            'index'  => Pages\ListPresupuestoCabeceras::route('/'),
            'create' => Pages\CreatePresupuestoCabecera::route('/create'),
            'edit'   => Pages\EditPresupuestoCabecera::route('/{record}/edit'),
        ];
    }
}
