<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OrdenCompraCabecera;
use App\Models\PresupuestoCabecera;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use App\Filament\Resources\OrdenCompraCabeceraResource\Pages;
use App\Filament\Resources\OrdenCompraCabeceraResource\RelationManagers;

class OrdenCompraCabeceraResource extends Resource
{
    protected static ?string $model = OrdenCompraCabecera::class;

    // Ajusta el ícono y el nombre
     protected static ?string $navigationGroup = 'Gestión Compras';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Orden de Compra';
    protected static ?string $pluralModelLabel = 'Ordenes de Compra';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([

            // SECCIÓN 1: CABECERA
            Forms\Components\Section::make('Información de Cabecera')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Select::make('cod_sucursal')
                                ->label('Sucursal')
                                ->relationship('sucursale', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn () => auth()->user()->cod_sucursal)
                                ->disabled()
                                ->dehydrated(true),

                            DatePicker::make('fec_orden')
                                ->label('Fecha de Orden')
                                ->default(fn () => now()->toDateString())
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),

                            Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor', 'cod_proveedor')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombre_completo ?? $record?->razon_social ?? ''
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('nro_presupuesto_ref', null))
                                ->disabled(fn (Get $get) => $get('nro_presupuesto_ref') !== null)
                                ->dehydrated(true),

                            Select::make('nro_presupuesto_ref')
                                ->label('Presupuesto de Referencia')
                                ->options(function ($record, Get $get) {
                                    $codProveedor = $get('cod_proveedor');
                                    if (!$codProveedor) return [];
                                    $presupuestosYaCargados = OrdenCompraCabecera::whereNotNull('nro_presupuesto_ref')
                                        ->when($record, fn ($q) => $q->where('nro_orden_compra', '!=', $record->nro_orden_compra))
                                        ->pluck('nro_presupuesto_ref')
                                        ->toArray();
                                    return PresupuestoCabecera::where('estado', 'APROBADO')
                                        ->where('cod_proveedor', $codProveedor)
                                        ->whereNotIn('nro_presupuesto', $presupuestosYaCargados)
                                        ->get()
                                        ->mapWithKeys(function ($p) {
                                            $label = 'Nro. ' . $p->nro_presupuesto;
                                            if ($p->fec_presupuesto) {
                                                $label .= ' — ' . \Carbon\Carbon::parse($p->fec_presupuesto)->format('d/m/Y');
                                            }
                                            return [$p->nro_presupuesto => $label];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->getOptionLabelUsing(function ($value) {
                                    $p = PresupuestoCabecera::find($value);
                                    if (!$p) return $value;
                                    $label = 'Nro. ' . $p->nro_presupuesto;
                                    if ($p->fec_presupuesto) {
                                        $label .= ' — ' . \Carbon\Carbon::parse($p->fec_presupuesto)->format('d/m/Y');
                                    }
                                    return $label;
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get, string $operation) {
                                    if (!$state || in_array($operation, ['view', 'edit'])) return;
                                    static::cargarDetallesDesdePresupuesto($state, $set, $get);
                                })
                                ->disabled(fn ($context) => in_array($context, ['edit', 'view'])),

                            Select::make('cod_condicion_compra')
                                ->label('Condición de Compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (Get $get) => $get('nro_presupuesto_ref') !== null)
                                ->dehydrated(true),

                            DatePicker::make('fec_entrega')
                                ->label('Fecha de Entrega')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->suffixIcon('heroicon-m-calendar-days'),

                            Textarea::make('observacion')
                                ->label('Observación')
                                ->maxLength(500)
                                ->columnSpan(3),

                            Forms\Components\Hidden::make('estado')->default('PENDIENTE'),
                        ]),
                ]),

            // SECCIÓN 2: DETALLES
            Forms\Components\Section::make('Detalles de la Orden')
                ->schema([
                    TableRepeater::make('ordenCompraDetalles')
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
                                ->disabled(fn (Get $get, $context) => in_array($context, ['edit', 'view']) || $get('../../nro_presupuesto_ref') !== null)
                                ->dehydrated(true)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if ($state) {
                                        $articulo = \App\Models\Articulos::find($state);
                                        if ($articulo) {
                                            $precio   = round($articulo->precio);
                                            $cantidad = (int) str_replace('.', '', (string)($get('cantidad') ?? 1));
                                            $total    = round($cantidad * $precio);
                                            $iva      = round($total / 11);
                                            $set('precio', number_format($precio, 0, ',', '.'));
                                            $set('total', number_format($total, 0, ',', '.'));
                                            $set('total_iva', number_format($iva, 0, ',', '.'));
                                        }
                                    }
                                    static::recalcularTotalesOrden($get, $set);
                                }),

                            Forms\Components\TextInput::make('precio')
                                ->label('Precio')
                                ->required()
                                ->suffix('₲')
                                ->formatStateUsing(fn ($state) => $state ? number_format((float) str_replace('.', '', (string)$state), 0, ',', '.') : '')
                                ->disabled(fn ($context) => in_array($context, ['edit', 'view']))
                                ->dehydrated(true)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $precio   = (float) str_replace('.', '', (string)($state ?? 0));
                                    $cantidad = (float) str_replace('.', '', (string)($get('cantidad') ?? 1));
                                    $total    = round($cantidad * $precio);
                                    $iva      = round($total / 11);
                                    $set('precio', number_format($precio, 0, ',', '.'));
                                    $set('total', number_format($total, 0, ',', '.'));
                                    $set('total_iva', number_format($iva, 0, ',', '.'));
                                    static::recalcularTotalesOrden($get, $set);
                                }),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->default(1)
                                ->required()
                                ->disabled(fn (Get $get, $context) => in_array($context, ['edit', 'view']) || $get('../../nro_presupuesto_ref') !== null)
                                ->dehydrated(true)
                                ->formatStateUsing(fn ($state) => $state !== null ? (int)$state : 1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) str_replace('.', '', (string)($state ?? 1));
                                    $precio   = (float) str_replace('.', '', (string)($get('precio') ?? 0));
                                    $total    = round($cantidad * $precio);
                                    $iva      = round($total / 11);
                                    $set('total', number_format($total, 0, ',', '.'));
                                    $set('total_iva', number_format($iva, 0, ',', '.'));
                                    static::recalcularTotalesOrden($get, $set);
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
                        ->addable(fn (Get $get, $context) => !in_array($context, ['edit', 'view']) && blank($get('nro_presupuesto_ref')))
                        ->deletable(fn (Get $get, $context) => !in_array($context, ['edit', 'view']) && blank($get('nro_presupuesto_ref')))
                        ->reorderable(fn (Get $get, $context) => !in_array($context, ['edit', 'view']) && blank($get('nro_presupuesto_ref')))
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $detalles = $get('ordenCompraDetalles') ?? [];
                            $grav = 0; $iva = 0;
                            foreach ($detalles as $d) {
                                $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                $grav += ($t - $iv);
                                $iva  += $iv;
                            }
                            $set('monto_gravado', number_format($grav, 0, ',', '.'));
                            $set('monto_tot_impuesto', number_format($iva, 0, ',', '.'));
                            $set('monto_general', number_format($grav + $iva, 0, ',', '.'));
                        })
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Get $get, Set $set) => (function () use ($get, $set) {
                                    $detalles = $get('ordenCompraDetalles') ?? [];
                                    $grav = 0; $iva = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $grav += ($t - $iv);
                                        $iva  += $iv;
                                    }
                                    $set('monto_gravado', number_format($grav, 0, ',', '.'));
                                    $set('monto_tot_impuesto', number_format($iva, 0, ',', '.'));
                                    $set('monto_general', number_format($grav + $iva, 0, ',', '.'));
                                })()
                            ),
                        ),
                ]),

            // SECCIÓN 3: TOTALES
            Forms\Components\Section::make('Totales')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('monto_gravado')
                                ->label('Total Gravada')
                                ->readOnly()
                                ->dehydrated(true)
                                ->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', (string)$state))
                                ->suffix('₲')
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    $detalles = $get('ordenCompraDetalles') ?? [];
                                    $grav = 0; $iva = 0;
                                    foreach ($detalles as $d) {
                                        $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
                                        $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
                                        $grav += ($t - $iv);
                                        $iva  += $iv;
                                    }
                                    $set('monto_gravado', number_format($grav, 0, ',', '.'));
                                    $set('monto_tot_impuesto', number_format($iva, 0, ',', '.'));
                                    $set('monto_general', number_format($grav + $iva, 0, ',', '.'));
                                }),

                            Forms\Components\TextInput::make('monto_tot_impuesto')
                                ->label('Total IVA')
                                ->readOnly()
                                ->dehydrated(true)
                                ->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', (string)$state))
                                ->suffix('₲'),

                            Forms\Components\TextInput::make('monto_general')
                                ->label('Total General')
                                ->readOnly()
                                ->dehydrated(true)
                                ->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', (string)$state))
                                ->suffix('₲'),
                        ]),
                ]),

            // SECCIÓN 4: AUDITORÍA
            Forms\Components\Section::make('Información de Auditoría')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('usuario_display')
                                ->label('Usuario Alta')
                                ->content(function ($record) {
                                    if ($record && $record->usuario_alta) {
                                        return $record->usuario_alta;
                                    }
                                    return auth()->user()->name ?? 'N/A';
                                }),
                            Forms\Components\Placeholder::make('fec_alta_display')
                                ->label('Fecha Alta')
                                ->content(function ($record) {
                                    if ($record && $record->fec_alta) {
                                        return \Carbon\Carbon::parse($record->fec_alta)->format('d/m/Y H:i');
                                    }
                                    return \Carbon\Carbon::now('America/Asuncion')->format('d/m/Y H:i');
                                }),
                        ]),
                ])
                ->collapsed()
                ->collapsible(),
        ]);
    }

    protected static function recalcularTotalesOrden(Get $get, Set $set): void
    {
        $detalles = $get('../../ordenCompraDetalles') ?? [];
        $grav = 0; $iva = 0;
        foreach ($detalles as $d) {
            $t  = (int) str_replace('.', '', (string)($d['total'] ?? 0));
            $iv = (int) str_replace('.', '', (string)($d['total_iva'] ?? 0));
            $grav += ($t - $iv);
            $iva  += $iv;
        }
        $set('../../monto_gravado', number_format($grav, 0, ',', '.'));
        $set('../../monto_tot_impuesto', number_format($iva, 0, ',', '.'));
        $set('../../monto_general', number_format($grav + $iva, 0, ',', '.'));
    }

    protected static function cargarDetallesDesdePresupuesto(int|string $nroPresupuesto, Set $set, Get $get): void
    {
        $presupuesto = PresupuestoCabecera::with(['presupuestoDetalles'])
            ->where('nro_presupuesto', $nroPresupuesto)
            ->first();

        if (!$presupuesto) return;

        $set('cod_proveedor', $presupuesto->cod_proveedor);
        $set('cod_condicion_compra', $presupuesto->cod_condicion_compra);
        $set('observacion', 'Basado en presupuesto Nro. ' . $nroPresupuesto);

        $items = [];
        $grav = 0; $ivaTot = 0;
        foreach ($presupuesto->presupuestoDetalles as $d) {
            $total = (float)($d->total ?? 0);
            $iva   = (float)($d->total_iva ?? 0);
            $grav   += ($total - $iva);
            $ivaTot += $iva;
            $items[] = [
                'cod_articulo' => $d->cod_articulo,
                'cantidad'     => (int)($d->cantidad ?? 0),
                'precio'       => number_format((float)($d->precio ?? 0), 0, ',', '.'),
                'total'        => number_format($total, 0, ',', '.'),
                'total_iva'    => number_format($iva, 0, ',', '.'),
            ];
        }

        $set('ordenCompraDetalles', $items);
        $set('monto_gravado', number_format($grav, 0, ',', '.'));
        $set('monto_tot_impuesto', number_format($ivaTot, 0, ',', '.'));
        $set('monto_general', number_format($grav + $ivaTot, 0, ',', '.'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('nro_orden_compra')
                    ->numeric()
                    ->label('Nro.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                 ->label('Proveedor')
                 ->searchable(),
                   // ->sortable(),
                Tables\Columns\TextColumn::make('fec_orden')
                    ->date('d/m/Y'),
                    //->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                 ->label('Condicion'),
                   // ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'APROBADO' => 'success',
                    'ANULADO'  => 'danger',
                    default    => 'warning',
                })
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
              ActionGroup::make([
        Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('info')
            ->icon('heroicon-m-eye')
            ->modalWidth('7xl')
            ->modalHeading(fn (OrdenCompraCabecera $record) => 'Vista Orden de Compra N° ' . $record->nro_orden_compra)
            ->mutateRecordDataUsing(function (array $data, OrdenCompraCabecera $record): array {
                $detalles = \App\Models\OrdenCompraDetalle::where('nro_orden_compra', $record->nro_orden_compra)->get();
                $data['ordenCompraDetalles'] = $detalles->map(fn ($d) => [
                    'cod_articulo' => $d->cod_articulo,
                    'cantidad'     => (int)$d->cantidad,
                    'precio'       => number_format((float)$d->precio, 0, ',', '.'),
                    'total'        => number_format((float)$d->total, 0, ',', '.'),
                    'total_iva'    => number_format((float)$d->total_iva, 0, ',', '.'),
                ])->toArray();
                return $data;
            }),

        Tables\Actions\Action::make('imprimir')
            ->label('Imprimir PDF')
            ->icon('heroicon-m-printer')
            ->color('warning')
            ->url(fn (OrdenCompraCabecera $record) => route('orden-compra.pdf', $record))
            ->openUrlInNewTab(),

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Anular Orden de Compra')
            ->modalDescription('¿Está seguro que desea anular esta orden de compra? Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Sí, anular')
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado === 'PENDIENTE')
            ->action(function (OrdenCompraCabecera $record) {
                $record->update([
                    'estado'              => 'ANULADO',
                    'nro_presupuesto_ref' => null,
                ]);
                
                \Filament\Notifications\Notification::make()
                    ->title('Orden de compra anulada')
                    ->body("La orden de compra Nro. {$record->nro_orden_compra} ha sido anulada exitosamente.")
                    ->success()
                    ->icon('heroicon-o-check-circle')
                    ->duration(5000)
                    ->send();
            }),

        Tables\Actions\Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Aprobar Orden de Compra')
            ->modalDescription('¿Está seguro que desea aprobar esta orden de compra?')
            ->modalSubmitActionLabel('Sí, aprobar')
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado === 'PENDIENTE')
            ->action(function (OrdenCompraCabecera $record) {
                $record->update(['estado' => 'APROBADO']);
                
                \Filament\Notifications\Notification::make()
                    ->title('Orden de compra aprobada')
                    ->body("La orden de compra Nro. {$record->nro_orden_compra} ha sido aprobada exitosamente.")
                    ->success()
                    ->icon('heroicon-o-check-badge')
                    ->duration(5000)
                    ->send();
            }),

        Tables\Actions\Action::make('crear_factura')
            ->label('Crear Factura')
            ->icon('heroicon-m-document-plus')
            ->color('info')
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado === 'APROBADO')
            ->url(fn (OrdenCompraCabecera $record) => 
                \App\Filament\Resources\CompraCabeceraResource::getUrl('create', ['orden_compra' => $record->nro_orden_compra])
            )
            ->tooltip('Crear una factura de compra basada en esta orden'),
    ])
        ->label('Opciones')                      // texto del botón (opcional)
        ->icon('heroicon-m-ellipsis-vertical'),  // ícono de “tres puntitos”
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
            'index'  => Pages\ListOrdenCompraCabeceras::route('/'),
            'create' => Pages\CreateOrdenCompraCabecera::route('/create'),
            'edit'   => Pages\EditOrdenCompraCabecera::route('/{record}/edit'),
        ];
    }
}
