<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReclamoResource\Pages;
use App\Models\Reclamo;
use App\Models\OrdenServicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;

class ReclamoResource extends Resource
{
    protected static ?string $model = Reclamo::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationGroup = 'Gestión Servicios';
    protected static ?string $modelLabel = 'Reclamo';
    protected static ?string $pluralModelLabel = 'Reclamos';
    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ─── SECCIÓN 1: ORDEN DE SERVICIO ───
                Section::make('')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('orden_servicio_id')
                                    ->label('Orden de Servicio')
                                    ->prefixIcon('heroicon-o-wrench-screwdriver')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return OrdenServicio::where('facturado', true)
                                            ->with('cliente.persona')
                                            ->get()
                                            ->mapWithKeys(function ($os) {
                                                $cliente = $os->cliente?->nombre_completo ?? 'Sin cliente';
                                                return [$os->id => "OS #{$os->id} - {$cliente}"];
                                            });
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $os = OrdenServicio::with([
                                                'cliente.persona',
                                                'recepcionVehiculo.vehiculo.marca',
                                                'recepcionVehiculo.vehiculo.modelo',
                                                'diagnostico.recepcionVehiculo.vehiculo.marca',
                                                'diagnostico.recepcionVehiculo.vehiculo.modelo',
                                                'presupuestoVenta.recepcionVehiculo.vehiculo.marca',
                                                'presupuestoVenta.recepcionVehiculo.vehiculo.modelo',
                                            ])->find($state);
                                            if ($os) {
                                                $cliente = $os->cliente;
                                                $persona = $cliente?->persona;
                                                
                                                $set('cod_cliente', $cliente?->cod_cliente ?? null);
                                                $set('cliente_nombre', $persona?->nombre_completo ?? 'N/A');
                                                $set('cliente_documento', $persona?->nro_documento ?? 'N/A');
                                                $set('cod_sucursal', $os->cod_sucursal);
                                                
                                                $v = null;
                                                if ($os->recepcionVehiculo?->vehiculo) {
                                                    $v = $os->recepcionVehiculo->vehiculo;
                                                } elseif ($os->diagnostico?->recepcionVehiculo?->vehiculo) {
                                                    $v = $os->diagnostico->recepcionVehiculo->vehiculo;
                                                } elseif ($os->presupuestoVenta?->recepcionVehiculo?->vehiculo) {
                                                    $v = $os->presupuestoVenta->recepcionVehiculo->vehiculo;
                                                }
                                                
                                                $set('vehiculo_info', $v 
                                                    ? "{$v->marca?->descripcion} {$v->modelo?->descripcion} - {$v->matricula}"
                                                    : 'Sin vehículo'
                                                );
                                            }
                                        }
                                    })
                                    ->placeholder('Seleccione una OS facturada'),

                                TextInput::make('cliente_nombre')
                                    ->label('Cliente')
                                    ->prefixIcon('heroicon-o-user')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('—'),

                                TextInput::make('cliente_documento')
                                    ->label('Documento')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('—'),
                            ]),

                        TextInput::make('vehiculo_info')
                            ->label('Vehículo')
                            ->prefixIcon('heroicon-o-truck')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—')
                            ->columnSpanFull(),

                        Hidden::make('cod_cliente')
                            ->dehydrated(),
                        Hidden::make('cod_sucursal')
                            ->dehydrated()
                            ->default(fn () => auth()->user()?->cod_sucursal),
                    ]),

                // ─── SECCIÓN 2: DETALLES DEL RECLAMO ───
                Section::make('')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('cod_tipo_reclamo')
                                    ->label('Tipo de Reclamo')
                                    ->prefixIcon('heroicon-o-tag')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->relationship('tipoReclamo', 'descripcion', function ($query) {
                                        return $query->where('activo', true);
                                    })
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->descripcion)
                                    ->placeholder('Seleccione el tipo')
                                    ->columnSpan(2),

                                Select::make('prioridad')
                                    ->label('Prioridad')
                                    ->prefixIcon('heroicon-o-fire')
                                    ->required()
                                    ->options([
                                        'Baja' => 'Baja',
                                        'Media' => 'Media',
                                        'Alta' => 'Alta',
                                        'Crítica' => 'Crítica',
                                    ])
                                    ->default('Media')
                                    ->native(false)
                                    ->extraAttributes(function ($state) {
                                        return match ($state) {
                                            'Baja' => ['style' => 'color: #6b7280;'],
                                            'Media' => ['style' => 'color: #f59e0b;'],
                                            'Alta' => ['style' => 'color: #ef4444;'],
                                            'Crítica' => ['style' => 'color: #dc2626; font-weight: bold;'],
                                            default => [],
                                        };
                                    }),

                                DatePicker::make('fecha_reclamo')
                                    ->label('Fecha del Reclamo')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->native(true)
                                    ->displayFormat('d/m/Y'),
                            ]),

                        Textarea::make('descripcion')
                            ->label('Descripción del Reclamo')
                            ->required()
                            ->placeholder('Describa detalladamente el problema o queja del cliente...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ─── SECCIÓN 3: SEGUIMIENTO ───
                Section::make('')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('estado')
                                    ->label('Estado')
                                    ->prefixIcon('heroicon-o-flag')
                                    ->required()
                                    ->options([
                                        'Pendiente' => 'Pendiente',
                                        'En Proceso' => 'En Proceso',
                                        'Resuelto' => 'Resuelto',
                                        'Rechazado' => 'Rechazado',
                                        'Cerrado' => 'Cerrado',
                                    ])
                                    ->default('Pendiente')
                                    ->native(false)
                                    ->extraAttributes(function ($state) {
                                        return match ($state) {
                                            'Pendiente' => ['style' => 'color: #f59e0b;'],
                                            'En Proceso' => ['style' => 'color: #3b82f6;'],
                                            'Resuelto' => ['style' => 'color: #22c55e;'],
                                            'Rechazado' => ['style' => 'color: #ef4444;'],
                                            'Cerrado' => ['style' => 'color: #6b7280;'],
                                            default => [],
                                        };
                                    }),

                                TextInput::make('responsable')
                                    ->label('Responsable')
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Quién gestiona el reclamo')
                                    ->maxLength(100),

                                DatePicker::make('fecha_resolucion')
                                    ->label('Fecha de Resolución')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->nullable()
                                    ->native(true)
                                    ->displayFormat('d/m/Y')
                                    ->visible(fn ($get) => in_array($get('estado'), ['Resuelto', 'Cerrado'])),
                            ]),

                        Textarea::make('accion_tomada')
                            ->label('Acción Tomada')
                            ->placeholder('Describa qué acciones se realizaron para resolver el reclamo...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('resolucion')
                            ->label('Resolución')
                            ->placeholder('Describa cómo se resolvió el reclamo...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // ─── SECCIÓN 4: INFO DEL REGISTRO ───
                Section::make('')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('sucursal_nombre')
                                ->label('Sucursal')
                                ->prefixIcon('heroicon-o-building-storefront')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function () {
                                    $user = auth()->user();
                                    return $user?->sucursal?->descripcion ?? 'Sucursal Principal';
                                }),

                            TextInput::make('fecha_alta_display')
                                ->label('Fecha de Registro')
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => now()->format('d/m/Y H:i')),

                            TextInput::make('usuario_nombre')
                                ->label('Usuario')
                                ->prefixIcon('heroicon-o-user-circle')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => auth()->user()?->name ?? 'Sistema'),
                        ]),
                    ]),

                // Campos ocultos para BD
                Hidden::make('usuario_alta')
                    ->dehydrated()
                    ->default(fn () => auth()->user()?->id),

                Hidden::make('fecha_alta')
                    ->dehydrated()
                    ->default(fn () => now()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ordenServicio.cliente.persona',
                'ordenServicio.recepcionVehiculo.vehiculo.marca',
                'ordenServicio.recepcionVehiculo.vehiculo.modelo',
                'ordenServicio.diagnostico.recepcionVehiculo.vehiculo.marca',
                'ordenServicio.diagnostico.recepcionVehiculo.vehiculo.modelo',
                'tipoReclamo',
                'sucursal',
                'usuarioAlta',
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('ordenServicio.id')
                                ->label('Orden de Servicio')
                                ->formatStateUsing(fn ($state) => "OS #{$state}"),

                            TextEntry::make('cliente.nombre_completo')
                                ->label('Cliente')
                                ->default('Sin cliente'),

                            TextEntry::make('cliente.persona.nro_documento')
                                ->label('Documento')
                                ->default('N/A'),
                        ]),

                        TextEntry::make('vehiculo_display')
                            ->label('Vehículo')
                            ->default('Sin vehículo')
                            ->getStateUsing(function ($record) {
                                $os = $record->ordenServicio;
                                $v = null;
                                if ($os->recepcionVehiculo?->vehiculo) {
                                    $v = $os->recepcionVehiculo->vehiculo;
                                } elseif ($os->diagnostico?->recepcionVehiculo?->vehiculo) {
                                    $v = $os->diagnostico->recepcionVehiculo->vehiculo;
                                } elseif ($os->presupuestoVenta?->recepcionVehiculo?->vehiculo) {
                                    $v = $os->presupuestoVenta->recepcionVehiculo->vehiculo;
                                }
                                return $v
                                    ? "{$v->marca?->descripcion} {$v->modelo?->descripcion} - {$v->matricula}"
                                    : 'Sin vehículo';
                            }),
                    ]),

                InfoSection::make('')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('tipoReclamo.descripcion')
                                ->label('Tipo de Reclamo')
                                ->default('N/A'),

                            TextEntry::make('prioridad')
                                ->label('Prioridad')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Baja' => 'gray',
                                    'Media' => 'warning',
                                    'Alta' => 'danger',
                                    'Crítica' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('fecha_reclamo')
                                ->label('Fecha del Reclamo')
                                ->date('d/m/Y'),

                            TextEntry::make('estado')
                                ->label('Estado')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Pendiente' => 'warning',
                                    'En Proceso' => 'primary',
                                    'Resuelto' => 'success',
                                    'Rechazado' => 'danger',
                                    'Cerrado' => 'gray',
                                    default => 'gray',
                                }),
                        ]),

                        TextEntry::make('descripcion')
                            ->label('Descripción del Reclamo')
                            ->columnSpanFull(),
                    ]),

                InfoSection::make('')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('responsable')
                                ->label('Responsable')
                                ->placeholder('Sin asignar'),

                            TextEntry::make('fecha_resolucion')
                                ->label('Fecha de Resolución')
                                ->date('d/m/Y')
                                ->placeholder('—'),
                        ]),

                        TextEntry::make('accion_tomada')
                            ->label('Acción Tomada')
                            ->placeholder('—'),

                        TextEntry::make('resolucion')
                            ->label('Resolución')
                            ->placeholder('—'),
                    ])
                    ->visible(fn ($record) => $record->estado !== 'Pendiente'),

                InfoSection::make('')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('sucursal.descripcion')
                                ->label('Sucursal')
                                ->default('Sucursal Principal'),

                            TextEntry::make('fecha_alta')
                                ->label('Fecha de Registro')
                                ->dateTime('d/m/Y H:i'),

                            TextEntry::make('usuario_alta')
                                ->label('Registrado por')
                                ->default('Sistema'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ordenServicio.id')
                    ->label('OS #')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->url(fn ($record) => "/admin/orden-servicios/{$record->orden_servicio_id}")
                    ->openUrlInNewTab(),

                TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->getStateUsing(function ($record) {
                        return $record->cliente?->nombre_completo ?? 'N/A';
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('cliente.persona', function ($q) use ($search) {
                            $q->where('nombres', 'ilike', "%{$search}%")
                              ->orWhere('apellidos', 'ilike', "%{$search}%")
                              ->orWhere('razon_social', 'ilike', "%{$search}%");
                        });
                    })
                    ->limit(18)
                    ->extraCellAttributes([
                        'style' => 'max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
                    ]),

                TextColumn::make('fecha_reclamo')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('tipoReclamo.descripcion')
                    ->label('Tipo')
                    ->getStateUsing(fn ($record) => $record->tipoReclamo?->descripcion ?? 'N/A')
                    ->colors([
                        'Falla de Repuesto' => 'danger',
                        'Demora en el Servicio' => 'warning',
                        'Calidad de Servicio' => 'primary',
                        'Atención al Cliente' => 'info',
                        'Precio/Facturación' => 'success',
                        'Otros' => 'gray',
                    ])
                    ->width('100px')
                    ->searchable(),

                BadgeColumn::make('prioridad')
                    ->label('Prioridad')
                    ->colors([
                        'Baja' => 'gray',
                        'Media' => 'warning',
                        'Alta' => 'danger',
                        'Crítica' => 'danger',
                    ])
                    ->icons([
                        'Baja' => 'heroicon-m-arrow-down',
                        'Media' => 'heroicon-m-arrow-right',
                        'Alta' => 'heroicon-m-arrow-up',
                        'Crítica' => 'heroicon-m-exclamation-triangle',
                    ])
                    ->width('90px')
                    ->sortable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'Pendiente' => 'warning',
                        'En Proceso' => 'primary',
                        'Resuelto' => 'success',
                        'Rechazado' => 'danger',
                        'Cerrado' => 'gray',
                    ])
                    ->icons([
                        'Pendiente' => 'heroicon-m-clock',
                        'En Proceso' => 'heroicon-m-arrow-path',
                        'Resuelto' => 'heroicon-m-check-circle',
                        'Rechazado' => 'heroicon-m-x-circle',
                        'Cerrado' => 'heroicon-m-lock-closed',
                    ])
                    ->width('100px')
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descripcion)
                    ->searchable()
                    ->width('200px'),

                TextColumn::make('responsable')
                    ->label('Responsable')
                    ->placeholder('Sin asignar')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fecha_resolucion')
                    ->label('Fecha Resolución')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('usuario_alta')
                    ->label('Registró')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_reclamo', 'desc')
            ->filters([
                SelectFilter::make('cod_tipo_reclamo')
                    ->label('Tipo')
                    ->relationship('tipoReclamo', 'descripcion', function ($query) {
                        return $query->where('activo', true);
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->descripcion),

                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'Baja' => 'Baja',
                        'Media' => 'Media',
                        'Alta' => 'Alta',
                        'Crítica' => 'Crítica',
                    ]),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Resuelto' => 'Resuelto',
                        'Rechazado' => 'Rechazado',
                        'Cerrado' => 'Cerrado',
                    ]),

                SelectFilter::make('fecha_reclamo')
                    ->label('Fecha Reclamo')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn ($query, $date) => $query->whereDate('fecha_reclamo', '>=', $date))
                            ->when($data['hasta'], fn ($query, $date) => $query->whereDate('fecha_reclamo', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReclamos::route('/'),
            'create' => Pages\CreateReclamo::route('/create'),
            'edit' => Pages\EditReclamo::route('/{record}/edit'),
        ];
    }
}
