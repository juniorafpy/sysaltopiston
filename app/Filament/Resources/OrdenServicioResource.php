<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdenServicioResource\Pages;
use App\Models\OrdenServicio;
use App\Models\PresupuestoVenta;
use App\Models\Empleados;
use App\Models\ExisteStock;
use App\Models\Articulos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class OrdenServicioResource extends Resource
{
    protected static ?string $model = OrdenServicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Órdenes de Servicio';

    protected static ?string $modelLabel = 'Orden de Servicio';

    protected static ?string $pluralModelLabel = 'Órdenes de Servicio';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make('Información de la Orden')
                            ->icon('heroicon-o-document-text')
                            ->columns(3)
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\Select::make('presupuesto_venta_id')
                                    ->label('Presupuesto de Venta')
                                    ->relationship('presupuestoVenta', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(function (?PresupuestoVenta $record): ?string {
                                        if (!$record) {
                                            return null;
                                        }
                                        $cliente = $record->cliente?->nombres ?? 'Sin cliente';
                                        $estado = $record->estado;
                                        return sprintf('#%s - %s - Estado: %s - Gs. %s',
                                            $record->id,
                                            Str::limit($cliente, 25),
                                            $estado,
                                            number_format($record->total, 0, ',', '.')
                                        );
                                    })
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        if (!$state) {
                                            // Limpiar campos si se deselecciona
                                            $set('diagnostico_id', null);
                                            $set('recepcion_vehiculo_id', null);
                                            $set('cliente_id', null);
                                            $set('total', 0);
                                            $set('detalles', []);
                                            return;
                                        }

                                        $presupuesto = PresupuestoVenta::with(['detalles.articulo', 'diagnostico', 'recepcionVehiculo', 'cliente'])->find($state);

                                        if ($presupuesto) {
                                            // Cargar datos de cabecera
                                            $set('diagnostico_id', $presupuesto->diagnostico_id);
                                            $set('recepcion_vehiculo_id', $presupuesto->recepcion_vehiculo_id);
                                            $set('cliente_id', $presupuesto->cliente_id);
                                            $set('total', $presupuesto->total);

                                            // Cargar detalles del presupuesto
                                            $detalles = [];
                                            foreach ($presupuesto->detalles as $detalle) {
                                                $detalles[] = [
                                                    'presupuesto_venta_detalle_id' => $detalle->id,
                                                    'cod_articulo' => $detalle->cod_articulo,
                                                    'descripcion' => $detalle->descripcion ?? $detalle->articulo?->descripcion,
                                                    'cantidad' => $detalle->cantidad,
                                                    'cantidad_utilizada' => 0,
                                                    'precio_unitario' => $detalle->precio_unitario,
                                                    'porcentaje_descuento' => $detalle->porcentaje_descuento ?? 0,
                                                    'monto_descuento' => $detalle->monto_descuento ?? 0,
                                                    'porcentaje_impuesto' => $detalle->porcentaje_impuesto ?? 10,
                                                    'monto_impuesto' => $detalle->monto_impuesto,
                                                    'subtotal' => $detalle->subtotal,
                                                    'total' => $detalle->total,
                                                    'stock_reservado' => false,
                                                ];
                                            }
                                            $set('detalles', $detalles);
                                        }
                                    })
                                    ->helperText('Seleccione un presupuesto aprobado para cargar sus datos automáticamente')
                                    ->columnSpan(3),

                                Forms\Components\Select::make('cliente_id')
                                    ->label('Cliente')
                                    ->relationship('cliente', 'nombres')
                                    ->searchable()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('mecanico_asignado_id')
                                    ->label('Mecánico Asignado')
                                    ->relationship('mecanicoAsignado', 'cod_empleado')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->persona?->nombres . ' ' . $record->persona?->apellidos ?? 'N/A')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('cod_sucursal')
                                    ->label('Sucursal')
                                    ->relationship('sucursal', 'descripcion')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn () => auth()->user()->cod_sucursal ?? null),

                                Forms\Components\DatePicker::make('fecha_inicio')
                                    ->label('Fecha Inicio')
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                Forms\Components\DatePicker::make('fecha_estimada_finalizacion')
                                    ->label('Fecha Estimada Finalización')
                                    ->native(false)
                                    ->after('fecha_inicio'),

                                Forms\Components\DatePicker::make('fecha_finalizacion_real')
                                    ->label('Fecha Finalización Real')
                                    ->native(false)
                                    ->visible(fn ($record) => $record && in_array($record->estado_trabajo, ['Finalizado', 'Facturado'])),

                                Forms\Components\Select::make('estado_trabajo')
                                    ->label('Estado')
                                    ->options([
                                        'Pendiente' => 'Pendiente',
                                        'En Proceso' => 'En Proceso',
                                        'Pausado' => 'Pausado',
                                        'Finalizado' => 'Finalizado',
                                        'Cancelado' => 'Cancelado',
                                        'Facturado' => 'Facturado',
                                    ])
                                    ->required()
                                    ->default('Pendiente')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $record) {
                                        if ($state === 'Finalizado' && !$record?->fecha_finalizacion_real) {
                                            $set('fecha_finalizacion_real', now());
                                        }
                                    }),

                                Forms\Components\Textarea::make('observaciones_tecnicas')
                                    ->label('Observaciones Técnicas')
                                    ->rows(3)
                                    ->columnSpan(3)
                                    ->placeholder('Detalles del trabajo realizado, repuestos utilizados, etc.'),

                                Forms\Components\Textarea::make('observaciones_internas')
                                    ->label('Observaciones Internas')
                                    ->rows(2)
                                    ->columnSpan(3)
                                    ->placeholder('Notas internas, no visibles para el cliente'),
                            ]),

                        Forms\Components\Section::make('Información del Sistema')
                            ->icon('heroicon-o-information-circle')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Placeholder::make('usuario_alta')
                                    ->label('Creado por')
                                    ->content(fn ($record) => $record?->usuario_alta ?? auth()->user()->name ?? 'N/A'),

                                Forms\Components\Placeholder::make('fec_alta')
                                    ->label('Fecha Alta')
                                    ->content(function ($record) {
                                        if (!$record || !$record->fec_alta) {
                                            return now()->format('d/m/Y H:i');
                                        }
                                        return $record->fec_alta->format('d/m/Y H:i');
                                    }),

                                Forms\Components\Placeholder::make('fec_mod')
                                    ->label('Última Modificación')
                                    ->content(function ($record) {
                                        if (!$record || !$record->fec_mod) {
                                            return 'Sin modificaciones';
                                        }
                                        return $record->fec_mod->format('d/m/Y H:i');
                                    })
                                    ->visible(fn ($record) => $record && $record->exists),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-blue-50 border-l-4 border-blue-400',
                            ]),
                    ]),

                Forms\Components\Section::make('Detalle de Artículos')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Artículos del presupuesto. Puede agregar artículos adicionales si fueron utilizados durante el servicio.')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->relationship('detalles')
                            ->columns(9)
                            ->schema([
                                Forms\Components\Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->options(\App\Models\Articulos::pluck('descripcion', 'cod_articulo'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        if (!$state) {
                                            return;
                                        }

                                        // Solo cargar precio si es un artículo nuevo (no del presupuesto)
                                        if ($get('presupuesto_venta_detalle_id')) {
                                            return;
                                        }

                                        $articulo = \App\Models\Articulos::find($state);
                                        if (!$articulo) {
                                            return;
                                        }

                                        $set('descripcion', $articulo->descripcion);
                                        $set('precio_unitario', $articulo->precio);
                                        $set('porcentaje_impuesto', 10); // IVA por defecto

                                        // Verificar si hay promoción vigente
                                        $porcentajeDescuento = \App\Models\Promocion::getDescuentoVigente($state) ?? 0;

                                        // Calcular importes con promoción
                                        $cantidad = $get('cantidad') ?? 1;
                                        $precioUnit = $articulo->precio;
                                        $montoDescuento = ($cantidad * $precioUnit) * ($porcentajeDescuento / 100);
                                        $subtotal = ($cantidad * $precioUnit) - $montoDescuento;
                                        $impuesto = $subtotal * 0.10;
                                        $total = $subtotal + $impuesto;

                                        $set('porcentaje_descuento', $porcentajeDescuento);
                                        $set('monto_descuento', $montoDescuento);
                                        $set('subtotal', $subtotal);
                                        $set('monto_impuesto', $impuesto);
                                        $set('total', $total);

                                        // Verificar stock disponible
                                        $codSucursal = auth()->user()->cod_sucursal ?? ($livewire->record->cod_sucursal ?? null);

                                        if ($codSucursal) {
                                            $stock = ExisteStock::where('cod_articulo', $state)
                                                ->where('cod_sucursal', $codSucursal)
                                                ->first();

                                            if ($stock) {
                                                $stockDisponible = $stock->stock_actual - $stock->stock_reservado;

                                                \Filament\Notifications\Notification::make()
                                                    ->info()
                                                    ->title('Stock disponible')
                                                    ->body("Hay {$stockDisponible} unidades disponibles de {$articulo->descripcion}")
                                                    ->send();
                                            } else {
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('Sin stock registrado')
                                                    ->body("No existe registro de stock para {$articulo->descripcion} en esta sucursal")
                                                    ->send();
                                            }
                                        }

                                        // Notificar si hay promoción
                                        if ($porcentajeDescuento > 0) {
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('¡Promoción aplicada!')
                                                ->body("Descuento del {$porcentajeDescuento}% aplicado por promoción vigente")
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(2)
                                    ->disabled(fn (callable $get) => $get('presupuesto_venta_detalle_id') !== null)
                                    ->dehydrated()
                                    ->helperText(fn (callable $get) => $get('presupuesto_venta_detalle_id')
                                        ? '🔒 Del presupuesto (no editable)'
                                        : '🆕 Artículo adicional'),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->required()
                                    ->suffix('u')
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        // Calcular totales
                                        $precioUnit = $get('precio_unitario') ?? 0;
                                        $porcentajeDescuento = $get('porcentaje_descuento') ?? 0;
                                        $montoDescuento = ($state * $precioUnit) * ($porcentajeDescuento / 100);
                                        $subtotal = ($state * $precioUnit) - $montoDescuento;
                                        $impuesto = $subtotal * 0.10;

                                        $set('monto_descuento', $montoDescuento);
                                        $set('subtotal', $subtotal);
                                        $set('monto_impuesto', $impuesto);
                                        $set('total', $subtotal + $impuesto);

                                        // Validar stock disponible
                                        $codArticulo = $get('cod_articulo');
                                        $cantidad = floatval($state);

                                        if ($codArticulo && $cantidad > 0) {
                                            // Obtener sucursal del usuario o del registro
                                            $codSucursal = auth()->user()->cod_sucursal ?? ($livewire->record->cod_sucursal ?? null);

                                            if ($codSucursal) {
                                                // Buscar stock en la sucursal
                                                $stock = ExisteStock::where('cod_articulo', $codArticulo)
                                                    ->where('cod_sucursal', $codSucursal)
                                                    ->first();

                                                if ($stock) {
                                                    $stockDisponible = $stock->stock_actual - $stock->stock_reservado;

                                                    if ($cantidad > $stockDisponible) {
                                                        // Obtener nombre del artículo para el mensaje
                                                        $articulo = Articulos::find($codArticulo);
                                                        $nombreArticulo = $articulo ? $articulo->descripcion : 'este artículo';

                                                        Notification::make()
                                                            ->warning()
                                                            ->title('Stock insuficiente')
                                                            ->body("Solo hay {$stockDisponible} unidades disponibles de {$nombreArticulo} en esta sucursal. Solicitado: {$cantidad}")
                                                            ->persistent()
                                                            ->send();
                                                    } elseif ($stockDisponible > 0 && $cantidad <= $stockDisponible) {
                                                        Notification::make()
                                                            ->success()
                                                            ->title('Stock disponible')
                                                            ->body("Hay {$stockDisponible} unidades disponibles")
                                                            ->send();
                                                    }
                                                } else {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Sin stock registrado')
                                                        ->body('No existe registro de stock para este artículo en esta sucursal')
                                                        ->send();
                                                }
                                            }
                                        }
                                    })
                                    ->disabled(fn (callable $get) => $get('presupuesto_venta_detalle_id') !== null)
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('cantidad_utilizada')
                                    ->label('Cant. Usada')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(fn (callable $get) => $get('cantidad'))
                                    ->suffix('u')
                                    ->helperText('Cantidad realmente utilizada'),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio Unit.')
                                    ->numeric()
                                    ->prefix('Gs.')
                                    ->required()
                                    ->disabled(fn (callable $get) => $get('presupuesto_venta_detalle_id') !== null)
                                    ->dehydrated()
                                    ->helperText('Seleccione primero un artículo para cargar el precio'),

                                Forms\Components\TextInput::make('porcentaje_descuento')
                                    ->label('% Desc.')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $cantidad = $get('cantidad') ?? 0;
                                        $precioUnit = $get('precio_unitario') ?? 0;
                                        $porcentajeDescuento = $state ?? 0;

                                        $montoDescuento = ($cantidad * $precioUnit) * ($porcentajeDescuento / 100);
                                        $subtotal = ($cantidad * $precioUnit) - $montoDescuento;
                                        $impuesto = $subtotal * 0.10;

                                        $set('monto_descuento', $montoDescuento);
                                        $set('subtotal', $subtotal);
                                        $set('monto_impuesto', $impuesto);
                                        $set('total', $subtotal + $impuesto);
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Gs.'),

                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('Gs.'),

                                Forms\Components\Toggle::make('stock_reservado')
                                    ->label('Stock Reservado')
                                    ->disabled()
                                    ->dehydrated()
                                    ->inline(false),

                                // Campos hidden para datos necesarios
                                Forms\Components\Hidden::make('descripcion'),
                                Forms\Components\Hidden::make('presupuesto_venta_detalle_id'),
                                Forms\Components\Hidden::make('monto_descuento'),
                                Forms\Components\Hidden::make('porcentaje_impuesto'),
                                Forms\Components\Hidden::make('monto_impuesto'),
                            ])
                            ->disabled(fn ($record) => $record && !$record->puedeEditarse())
                            ->addable(fn ($record) => !$record || $record->puedeEditarse())
                            ->deletable(fn ($record) => !$record || $record->puedeEditarse())
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->addActionLabel('+ Agregar artículo adicional')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['descripcion'] ?? 'Nuevo artículo')
                            ->minItems(1)
                            ->live(),
                    ]),

                Forms\Components\Section::make('Totales')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('total')
                            ->label('Total General')
                            ->numeric()
                            ->disabled()
                            ->prefix('Gs.')
                            ->extraAttributes(['class' => 'text-xl font-bold']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('N° OS')
                    ->sortable()
                    ->searchable(),

               /* Tables\Columns\TextColumn::make('presupuestoVenta.id')
                    ->label('N° Presup.')
                    ->sortable(),*/

                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

             /*   Tables\Columns\TextColumn::make('recepcionVehiculo.vehiculo.matricula')
                    ->label('Vehículo')
                    ->searchable(),*/

                Tables\Columns\TextColumn::make('mecanicoAsignado.persona.nombres')
                    ->label('Mecánico')
                    ->limit(20),


                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_estimada_finalizacion')
                    ->label('Est. Fin')
                    ->date('d/m/Y'),


                Tables\Columns\BadgeColumn::make('estado_trabajo')
                    ->label('Estado')
                    ->colors([
                        'secondary' => 'Pendiente',
                        'warning' => 'En Proceso',
                        'danger' => 'Pausado',
                        'success' => 'Finalizado',
                        'gray' => 'Cancelado',
                        'primary' => 'Facturado',
                    ])
                    ->sortable(),

               /* Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('PYG')
                    ->sortable(),*/

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

            ])
           /* ->filters([
                Tables\Filters\SelectFilter::make('estado_trabajo')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Pausado' => 'Pausado',
                        'Finalizado' => 'Finalizado',
                        'Cancelado' => 'Cancelado',
                        'Facturado' => 'Facturado',
                    ]),
*/
               /* Tables\Filters\Filter::make('fecha_inicio')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($query, $date) => $query->whereDate('fecha_inicio', '>=', $date))
                            ->when($data['hasta'], fn ($query, $date) => $query->whereDate('fecha_inicio', '<=', $date));
                    }),
            ])*/
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('imprimir_pdf')
                        ->label('Imprimir OS')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(fn (OrdenServicio $record) => $record->generarPDF('download'))
                        ->tooltip('Descargar PDF de la Orden de Servicio'),

                    Tables\Actions\Action::make('ver_pdf')
                        ->label('Ver PDF')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->action(fn (OrdenServicio $record) => $record->generarPDF('stream'))
                        ->openUrlInNewTab()
                        ->tooltip('Ver PDF en el navegador'),

                    Tables\Actions\Action::make('finalizar')
                        ->label('Finalizar Trabajo')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (OrdenServicio $record): bool =>
                            in_array($record->estado_trabajo, ['Pendiente', 'En Proceso', 'Pausado'])
                        )
                        ->action(function (OrdenServicio $record): void {
                            $record->update([
                                'estado_trabajo' => 'Finalizado',
                                'fecha_finalizacion_real' => now(),
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Orden de servicio finalizada')
                                ->body('El trabajo ha sido marcado como finalizado.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancelar')
                        ->label('Cancelar OS')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (OrdenServicio $record): bool =>
                            $record->estado_trabajo !== 'Cancelado' && $record->estado_trabajo !== 'Facturado'
                        )
                        ->action(function (OrdenServicio $record): void {
                            // Liberar stock reservado
                            $record->liberarStock();

                            $record->update([
                                'estado_trabajo' => 'Cancelado',
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Orden de servicio cancelada')
                                ->body('El stock reservado ha sido liberado.')
                                ->send();
                        }),
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
            'index' => Pages\ListOrdenServicios::route('/'),
            'create' => Pages\CreateOrdenServicio::route('/create'),
            'edit' => Pages\EditOrdenServicio::route('/{record}/edit'),
            'view' => Pages\ViewOrdenServicio::route('/{record}'),
        ];
    }
}
