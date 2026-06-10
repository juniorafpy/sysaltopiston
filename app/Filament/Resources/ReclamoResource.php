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
                Section::make('Orden de Servicio')
                    ->description('Seleccione una orden de servicio ya facturada')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('orden_servicio_id')
                                    ->label('Orden de Servicio #')
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
                                            $os = OrdenServicio::with('cliente.persona')->find($state);
                                            if ($os) {
                                                $cliente = $os->cliente;
                                                $persona = $cliente?->persona;
                                                
                                                $set('cod_cliente', $cliente?->cod_cliente ?? null);
                                                $set('cliente_nombre', $persona?->nombre_completo ?? 'N/A');
                                                $set('cliente_documento', $persona?->nro_documento ?? 'N/A');
                                                $set('cod_sucursal', $os->cod_sucursal);
                                                
                                                // Cargar vehículo
                                                $rv = $os->recepcionVehiculo;
                                                $v = $rv?->vehiculo;
                                                $set('vehiculo_info', $v 
                                                    ? "{$v->marca?->descripcion} {$v->modelo?->descripcion} - {$v->matricula}"
                                                    : 'Sin vehículo'
                                                );
                                            }
                                        }
                                    })
                                    ->placeholder('Seleccione una OS facturada'),

                                Placeholder::make('cliente_nombre')
                                    ->label('Cliente')
                                    ->content(function (callable $get) {
                                        $ordenId = $get('orden_servicio_id');
                                        if (!$ordenId) return '—';
                                        
                                        $os = OrdenServicio::with('cliente.persona')->find($ordenId);
                                        return $os?->cliente?->persona?->nombre_completo ?? '—';
                                    }),

                                Placeholder::make('cliente_documento')
                                    ->label('Documento')
                                    ->content(function (callable $get) {
                                        $ordenId = $get('orden_servicio_id');
                                        if (!$ordenId) return '—';
                                        
                                        $os = OrdenServicio::with('cliente.persona')->find($ordenId);
                                        return $os?->cliente?->persona?->nro_documento ?? '—';
                                    }),

                                Placeholder::make('vehiculo_info')
                                    ->label('Vehículo')
                                    ->content(function (callable $get) {
                                        $ordenId = $get('orden_servicio_id');
                                        if (!$ordenId) return '—';
                                        
                                        $os = OrdenServicio::with('recepcionVehiculo.vehiculo.marca', 'recepcionVehiculo.vehiculo.modelo')->find($ordenId);
                                        $v = $os?->recepcionVehiculo?->vehiculo;
                                        return $v 
                                            ? "{$v->marca?->descripcion} {$v->modelo?->descripcion} - {$v->matricula}"
                                            : '—';
                                    }),
                            ]),

                        Hidden::make('cod_cliente'),
                        Hidden::make('cod_sucursal'),
                    ]),

                Section::make('Detalles del Reclamo')
                    ->description('Información sobre la queja o problema')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('cod_tipo_reclamo')
                                    ->label('Tipo de Reclamo')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->relationship('tipoReclamo', 'descripcion', function ($query) {
                                        return $query->where('activo', true);
                                    })
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->descripcion)
                                    ->placeholder('Seleccione el tipo'),

                                Select::make('prioridad')
                                    ->label('Prioridad')
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
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->native(false),
                            ]),

                        Textarea::make('descripcion')
                            ->label('Descripción del Reclamo')
                            ->required()
                            ->placeholder('Describa detalladamente el problema o queja del cliente...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Seguimiento y Resolución')
                    ->description('Estado y acciones tomadas para resolver el reclamo')
                    ->icon('heroicon-o-check-circle')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('estado')
                                    ->label('Estado')
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
                                    ->placeholder('Quién gestiona el reclamo')
                                    ->maxLength(100),

                                DatePicker::make('fecha_resolucion')
                                    ->label('Fecha de Resolución')
                                    ->nullable()
                                    ->native(false)
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

                Hidden::make('usuario_alta')
                    ->default(fn () => auth()->user()?->id),

                Hidden::make('fecha_alta')
                    ->default(fn () => now()),
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
                        return $query->whereHas('cliente', function ($q) use ($search) {
                            $q->where('nombres', 'ilike', "%{$search}%")
                              ->orWhere('apellidos', 'ilike', "%{$search}%")
                              ->orWhere('razon_social', 'ilike', "%{$search}%");
                        });
                    })
                    ->limit(30),

                TextColumn::make('fecha_reclamo')
                    ->label('Fecha')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d \d\e F \d\e Y') : '—')
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
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->descripcion)
                    ->searchable(),

                TextColumn::make('responsable')
                    ->label('Responsable')
                    ->placeholder('Sin asignar')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fecha_resolucion')
                    ->label('Fecha Resolución')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('usuarioAlta.name')
                    ->label('Registró')
                    ->getStateUsing(fn ($record) => $record->usuarioAlta?->name ?? 'N/A')
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
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewReclamo::route('/{record}'),
        ];
    }
}
