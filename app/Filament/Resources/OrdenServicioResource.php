<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdenServicioResource\Pages;
use App\Models\OrdenServicio;
use App\Models\PresupuestoVenta;
use App\Models\Articulos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class OrdenServicioResource extends Resource
{
    protected static ?string $model = OrdenServicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Lista de Orden de Servicios';

    protected static ?string $modelLabel = 'Orden de Servicio';

    protected static ?string $pluralModelLabel = 'Lista de Orden de Servicios';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Orden de Servicio')
                    ->icon('heroicon-o-document-text')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('presupuesto_venta_id')
                            ->label('Presupuesto')
                            ->relationship('presupuestoVenta', 'id')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(function (?PresupuestoVenta $record): ?string {
                                if (!$record) {
                                    return null;
                                }
                                // Obtener nombre del cliente - intenta primero nombres directo, luego persona
                                $cliente = $record->cliente?->nombres ??
                                           $record->cliente?->persona?->nombres ??
                                           'Sin cliente';
                                return sprintf('#%s - %s',
                                    $record->id,
                                    $cliente
                                );
                            })
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                if (!$state) {
                                    $set('diagnostico_id', null);
                                    $set('recepcion_vehiculo_id', null);
                                    $set('cod_cliente', null);
                                    $set('cod_mecanico', null);
                                    $set('cliente_nombre_valor', null);
                                    $set('mecanico_nombre_valor', null);
                                    $set('total', 0);
                                    $set('detalles', []);
                                    return;
                                }

                                $presupuesto = PresupuestoVenta::with([
                                    'detalles.articulo',
                                    'diagnostico.recepcionVehiculo.mecanico.empleado.persona',
                                    'cliente.persona',
                                    'recepcionVehiculo.mecanico.empleado.persona'
                                ])->find($state);

                                if ($presupuesto) {
                                    // Cargar datos de cabecera
                                    $set('diagnostico_id', $presupuesto->diagnostico_id);
                                    $set('recepcion_vehiculo_id', $presupuesto->recepcion_vehiculo_id);
                                    $set('cod_cliente', $presupuesto->cod_cliente);
                                    $set('total', $presupuesto->total);

                                    // Obtener y mostrar nombre del cliente directamente del presupuesto
                                    if ($presupuesto->cliente?->persona) {
                                        $clientePersona = $presupuesto->cliente->persona;
                                        $nombreCliente = ($clientePersona->nombres ?? '') . ' ' . ($clientePersona->apellidos ?? '');
                                        $set('cliente_nombre_valor', trim($nombreCliente));
                                    } else {
                                        $set('cliente_nombre_valor', 'Sin cliente');
                                    }

                                    // Obtener mecánico: primero intenta recepcionVehiculo directo, luego desde diagnostico
                                    $recepcion = $presupuesto->recepcionVehiculo ?? $presupuesto->diagnostico?->recepcionVehiculo;

                                    if ($recepcion?->cod_mecanico) {
                                        $set('cod_mecanico', $recepcion->cod_mecanico);

                                        // Obtener nombre del mecánico: mecanico -> empleado -> persona
                                        if ($recepcion->mecanico?->empleado?->persona) {
                                            $mecanicoPersona = $recepcion->mecanico->empleado->persona;
                                            $nombreMecanico = ($mecanicoPersona->nombres ?? '') . ' ' . ($mecanicoPersona->apellidos ?? '');
                                            $set('mecanico_nombre_valor', trim($nombreMecanico));
                                        } else {
                                            $set('mecanico_nombre_valor', 'Sin nombre');
                                        }
                                    } else {
                                        $set('mecanico_nombre_valor', 'Sin asignar');
                                    }

                                    // Cargar detalles del presupuesto
                                    $detalles = [];
                                    foreach ($presupuesto->detalles as $detalle) {
                                        if (empty($detalle->cod_articulo)) {
                                            continue;
                                        }

                                        $detalles[] = [
                                            'presupuesto_venta_detalle_id' => $detalle->id,
                                            'cod_articulo' => $detalle->cod_articulo,
                                            'descripcion' => $detalle->descripcion ?? $detalle->articulo?->descripcion,
                                            'cantidad' => $detalle->cantidad,
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
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('cliente_nombre')
                            ->label('Cliente')
                            ->content(function (callable $get, ?OrdenServicio $record) {
                                $nombreDesdeEstado = trim((string) ($get('cliente_nombre_valor') ?? ''));
                                if ($nombreDesdeEstado !== '') {
                                    return $nombreDesdeEstado;
                                }

                                $clientePersona = $record?->presupuestoVenta?->cliente?->persona;
                                if ($clientePersona) {
                                    return trim(($clientePersona->nombres ?? '') . ' ' . ($clientePersona->apellidos ?? ''));
                                }

                                return 'Sin cliente';
                            })
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('diagnostico_numero')
                            ->label('Diagnóstico #')
                            ->content(fn (callable $get) => 'Diag. #' . ($get('diagnostico_id') ?? 'N/A'))
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('mecanico_nombre')
                            ->label('Mecánico')
                            ->content(function (callable $get, ?OrdenServicio $record) {
                                $nombreDesdeEstado = trim((string) ($get('mecanico_nombre_valor') ?? ''));
                                if ($nombreDesdeEstado !== '') {
                                    return $nombreDesdeEstado;
                                }

                                $mecanicoPersona = $record?->mecanicoAsignado?->persona
                                    ?? $record?->presupuestoVenta?->recepcionVehiculo?->mecanico?->empleado?->persona
                                    ?? $record?->presupuestoVenta?->diagnostico?->recepcionVehiculo?->mecanico?->empleado?->persona;

                                if ($mecanicoPersona) {
                                    return trim(($mecanicoPersona->nombres ?? '') . ' ' . ($mecanicoPersona->apellidos ?? ''));
                                }

                                return 'Sin asignar';
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha')
                            ->required()
                            ->default(now())
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpan(1),

                        Forms\Components\Hidden::make('cod_cliente')
                            ->dehydrated(),

                        Forms\Components\Hidden::make('cod_mecanico')
                            ->dehydrated(),

                        Forms\Components\Hidden::make('cliente_nombre_valor'),

                        Forms\Components\Hidden::make('mecanico_nombre_valor'),

                        Forms\Components\Hidden::make('diagnostico_id')
                            ->dehydrated(),

                        Forms\Components\Hidden::make('recepcion_vehiculo_id')
                            ->dehydrated(),

                        Forms\Components\Hidden::make('cod_sucursal')
                            ->dehydrated()
                            ->default(fn () => auth()->user()->cod_sucursal ?? null),

                        Forms\Components\Select::make('estado_trabajo')
                            ->label('Estado')
                            ->options([
                                'En Proceso' => 'En Proceso',
                                'Pausado' => 'Pausado',
                                'Finalizado' => 'Finalizado',
                            ])
                            ->required()
                            ->default('En Proceso')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $record) {
                                if ($state === 'Finalizado' && !$record?->fecha_finalizacion_real) {
                                    $set('fecha_finalizacion_real', now());
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fecha_estimada_finalizacion')
                            ->label('Fecha Est. Fin.')
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->after('fecha_inicio')
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fecha_finalizacion_real')
                            ->label('Fecha Fin. Real')
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->disabled(fn ($record) => $record !== null)
                            ->visible(fn ($record) => $record && $record->estado_trabajo === 'Finalizado')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('observaciones_tecnicas')
                            ->label('Observaciones Técnicas')
                            ->rows(2)
                            ->columnSpan(4)
                            ->disabled(fn ($record) => $record !== null)
                            ->placeholder('Detalles del trabajo realizado...'),

                        Forms\Components\Textarea::make('observaciones_internas')
                            ->label('Observaciones Internas')
                            ->rows(2)
                            ->columnSpan(4)
                            ->disabled(fn ($record) => $record !== null)
                            ->placeholder('Notas internas...'),

                        Forms\Components\Hidden::make('total')
                            ->dehydrated(),
                    ]),

                Forms\Components\Section::make('Artículos Utilizados')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Solo ingrese cantidad utilizada. Los precios son consultivos.')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->relationship('detalles')
                            ->dehydrated(fn ($record) => $record === null)
                            ->columns(5)
                            ->columnSpan('full')
                            ->schema([
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Artículo')
                                    ->formatStateUsing(function ($state, callable $get) {
                                        $descripcion = trim((string) ($state ?? ''));
                                        if ($descripcion !== '') {
                                            return $descripcion;
                                        }

                                        $codArticulo = $get('cod_articulo');
                                        if (empty($codArticulo)) {
                                            return 'Sin descripción';
                                        }

                                        return Articulos::where('cod_articulo', $codArticulo)
                                            ->value('descripcion')
                                            ?? "Artículo #{$codArticulo}";
                                    })
                                    ->disabled()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant. Presupuestada')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('cod_articulo')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('presupuesto_venta_detalle_id')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('precio_unitario')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('porcentaje_descuento')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('monto_descuento')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('porcentaje_impuesto')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('monto_impuesto')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('subtotal')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('total')
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('stock_reservado')
                                    ->dehydrated(),
                            ])
                            ->disabled(fn ($record) => $record && !$record->puedeEditarse())
                            ->addable(fn ($record) => false)
                            ->deletable(fn ($record) => !$record || $record->puedeEditarse())
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['descripcion'] ?? 'Artículo')
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

                Tables\Columns\TextColumn::make('cliente_completo')
                    ->label('Cliente')
                    ->getStateUsing(function (OrdenServicio $record): string {
                        $cliente = $record->cliente
                            ?? $record->presupuestoVenta?->cliente;

                        $clientePersona = $cliente?->persona
                            ?? $record->presupuestoVenta?->cliente?->persona;

                        if (!empty($cliente?->nombre_completo)) {
                            return $cliente->nombre_completo;
                        }

                        if ($clientePersona) {
                            if (!empty($clientePersona->razon_social)) {
                                return trim((string) $clientePersona->razon_social);
                            }

                            $nombre = trim(($clientePersona->nombres ?? '') . ' ' . ($clientePersona->apellidos ?? ''));
                            if ($nombre !== '') {
                                return $nombre;
                            }
                        }

                        return 'Sin cliente';
                    })
                    ->limit(30),

             /*   Tables\Columns\TextColumn::make('recepcionVehiculo.vehiculo.matricula')
                    ->label('Vehículo')
                    ->searchable(),*/

                Tables\Columns\TextColumn::make('mecanico_completo')
                    ->label('Mecánico')
                    ->getStateUsing(function (OrdenServicio $record): string {
                        $mecanicoPersona = $record->mecanicoAsignado?->persona
                            ?? $record->presupuestoVenta?->recepcionVehiculo?->mecanico?->empleado?->persona
                            ?? $record->presupuestoVenta?->diagnostico?->recepcionVehiculo?->mecanico?->empleado?->persona;

                        if ($mecanicoPersona) {
                            $nombre = trim(($mecanicoPersona->nombres ?? '') . ' ' . ($mecanicoPersona->apellidos ?? ''));
                            if ($nombre !== '') {
                                return $nombre;
                            }
                        }

                        return 'Sin asignar';
                    })
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
                        'warning' => 'En Proceso',
                        'danger' => 'Pausado',
                        'success' => 'Finalizado',
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
                        ->url(fn (OrdenServicio $record): string => route('orden-servicio.pdf', $record))
                        ->openUrlInNewTab()
                        ->tooltip('Descargar PDF de la Orden de Servicio'),

                    Tables\Actions\Action::make('finalizar')
                        ->label('Finalizar Trabajo')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (OrdenServicio $record): bool =>
                            in_array($record->estado_trabajo, ['En Proceso', 'Pausado'])
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
