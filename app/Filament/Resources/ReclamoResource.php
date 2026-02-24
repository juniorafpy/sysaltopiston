<?php

namespace App\Filament\Resources;

use App\Models\Reclamo;
use App\Models\Personas;
use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\TipoReclamo;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ReclamoResource\Pages;

class ReclamoResource extends Resource
{
    protected static ?string $model = Reclamo::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Reclamos';

    protected static ?string $modelLabel = 'Reclamo';

    protected static ?string $pluralModelLabel = 'Reclamos';
    protected static ?string $navigationGroup = 'Servicios';
    //protected static ?string $navigationGroup = 'Servicio Técnico';

    protected static ?int $navigationSort = 22;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Reclamo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('cod_cliente')
                                    ->label('Cliente')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return Personas::query()
                                            ->selectRaw("cod_persona, CASE WHEN razon_social IS NOT NULL THEN razon_social ELSE CONCAT(nombres, ' ', apellidos) END as nombre_display")
                                            ->orderByRaw("CASE WHEN razon_social IS NOT NULL THEN razon_social ELSE CONCAT(nombres, ' ', apellidos) END")
                                            ->get()
                                            ->pluck('nombre_display', 'cod_persona');
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Personas::query()
                                            ->where(function ($query) use ($search) {
                                                $query->where('nombres', 'ilike', "%{$search}%")
                                                    ->orWhere('apellidos', 'ilike', "%{$search}%")
                                                    ->orWhere('razon_social', 'ilike', "%{$search}%")
                                                    ->orWhere('nro_documento', 'ilike', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($persona) {
                                                $nombre = $persona->razon_social ?? "{$persona->nombres} {$persona->apellidos}";
                                                return [$persona->cod_persona => $nombre];
                                            });
                                    })
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $persona = Personas::find($value);
                                        if (!$persona) return null;
                                        return $persona->razon_social ?? "{$persona->nombres} {$persona->apellidos}";
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        // Limpiar la OS seleccionada cuando cambia el cliente
                                        $set('orden_servicio_id', null);
                                        $set('matricula_vehiculo', null);
                                    })
                                    ->helperText('Buscar por nombre o documento'),

                                Select::make('orden_servicio_id')
                                    ->label('Orden de Servicio (OS) Ref.')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Forms\Get $get) {
                                        $codPersona = $get('cod_cliente');

                                        if (!$codPersona) {
                                            return [];
                                        }

                                        $cliente = Cliente::where('cod_persona', $codPersona)->first();

                                        if (!$cliente) {
                                            return [];
                                        }

                                        // Filtrar solo OS Finalizadas del cliente seleccionado
                                        return OrdenServicio::query()
                                            ->where('estado_trabajo', 'Finalizado')
                                            ->where(function ($query) use ($cliente, $codPersona) {
                                                $query->where('cod_cliente', $cliente->cod_cliente)
                                                    ->orWhereHas('recepcionVehiculo.cliente', function ($subQuery) use ($codPersona) {
                                                        $subQuery->where('cod_persona', $codPersona);
                                                    });
                                            })
                                            ->with('recepcionVehiculo.vehiculo')
                                            ->get()
                                            ->mapWithKeys(function ($os) {
                                                $matricula = $os->recepcionVehiculo?->vehiculo?->matricula ?? 'Sin vehículo';
                                                $fecha = $os->fecha_inicio?->format('d/m/Y') ?? '';
                                                return [$os->id => "OS #{$os->id} - {$matricula} ({$fecha})"];
                                            })
                                            ->toArray();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $os = OrdenServicio::with('recepcionVehiculo.vehiculo')->find($state);
                                            $vehiculo = $os?->recepcionVehiculo?->vehiculo;
                                            $matricula = $vehiculo?->matricula ?? 'N/A';
                                            $set('matricula_vehiculo', $matricula);
                                        }
                                    })
                                    ->helperText('Solo órdenes Finalizadas del cliente seleccionado')
                                    ->disabled(fn (Forms\Get $get) => !$get('cod_cliente')),                                Placeholder::make('matricula_vehiculo')
                                    ->label('Vehículo/Chapa')
                                    ->content(fn (Forms\Get $get): string => $get('matricula_vehiculo') ?? 'Seleccione una OS'),

                                Placeholder::make('usuario_alta_display')
                                    ->label('Usuario Registro')
                                    ->content(function (Forms\Get $get, $record) {
                                        if ($record?->usuarioAlta?->name) {
                                            return $record->usuarioAlta->name;
                                        }

                                        return Auth::user()?->name ?? 'N/A';
                                    }),

                                Placeholder::make('fecha_alta_display')
                                    ->label('Fecha Registro')
                                    ->content(function (Forms\Get $get, $record) {
                                        if ($record?->fecha_alta) {
                                            return $record->fecha_alta->format('d/m/Y H:i');
                                        }

                                        return now()->format('d/m/Y H:i');
                                    }),

                                Forms\Components\Hidden::make('usuario_alta')
                                    ->default(fn ($record) => $record?->usuario_alta ?? Auth::id())
                                    ->dehydrated(),

                                Forms\Components\Hidden::make('fecha_alta')
                                    ->default(fn ($record) => $record?->fecha_alta ?? now())
                                    ->dehydrated(),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Detalles del Reclamo')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('fecha_reclamo')
                                    ->label('Fecha del Reclamo')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Select::make('cod_tipo_reclamo')
                                    ->label('Tipo de Reclamo')
                                    ->required()
                                    ->options(TipoReclamo::where('activo', true)->pluck('descripcion', 'cod_tipo_reclamo'))
                                    ->preload()
                                    ->searchable(),

                                Select::make('prioridad')
                                    ->label('Prioridad')
                                    ->required()
                                    ->options([
                                        'Alta' => 'Alta',
                                        'Media' => 'Media',
                                        'Baja' => 'Baja',
                                    ])
                                    ->default('Media')
                                    ->native(false),
                            ]),

                        Textarea::make('descripcion')
                            ->label('Descripción Detallada del Reclamo')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->helperText('Describa detalladamente el motivo del reclamo'),
                    ])
                    ->columns(3),

                Section::make('Auditoría')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('usuario_alta_nombre')
                                    ->label('Usuario Registro')
                                    ->content(fn ($record) => $record?->usuarioAlta?->name ?? 'N/A'),

                                Placeholder::make('fecha_alta')
                                    ->label('Fecha Registro')
                                    ->content(fn ($record) => $record?->fecha_alta?->format('d/m/Y H:i') ?? 'N/A'),

                                Placeholder::make('sucursal_nombre')
                                    ->label('Sucursal')
                                    ->content(fn ($record) => $record?->sucursal?->denominacion ?? 'N/A'),
                            ]),
                    ])
                    ->columns(3)
                    ->visible(fn (string $operation) => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cod_reclamo')
                    ->label('Nº')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fecha_reclamo')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('cliente.nombres')
                    ->label('Cliente')
                    ->formatStateUsing(function ($record) {
                        $persona = $record->cliente;
                        return $persona->razon_social ?? "{$persona->nombres} {$persona->apellidos}";
                    })
                    ->searchable(['nombres', 'apellidos', 'razon_social'])
                    ->sortable()
                    ->limit(30),

             /*   TextColumn::make('ordenServicio.id')
                    ->label('OS Ref.')
                    ->formatStateUsing(fn ($state) => "OS #{$state}")
                    ->sortable(),*/

             /*   TextColumn::make('matricula')
                    ->label('Vehículo')
                    ->getStateUsing(fn ($record) => $record->ordenServicio?->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A'),
*/
                TextColumn::make('tipoReclamo.descripcion')
                    ->label('Tipo')
                    ->sortable()
                    ->limit(20),

           /*     BadgeColumn::make('prioridad')
                    ->label('Prioridad')
                    ->colors([
                        'danger' => 'Alta',
                        'warning' => 'Media',
                        'success' => 'Baja',
                    ])
                    ->sortable(),*/

                TextColumn::make('usuarioAlta.name')
                    ->label('Registrado por')
                    ->sortable(),
                  //  ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
               /* SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'En Proceso' => 'En Proceso',
                        'Resuelto' => 'Resuelto',
                        'Cerrado' => 'Cerrado',
                    ])
                    ->default('Pendiente'),*/

              /*  SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'Alta' => 'Alta',
                        'Media' => 'Media',
                        'Baja' => 'Baja',
                    ]),*/

              /*  SelectFilter::make('cod_tipo_reclamo')
                    ->label('Tipo de Reclamo')
                    ->relationship('tipoReclamo', 'descripcion'),*/
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('ver')
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn (Reclamo $record) => 'Reclamo #' . $record->cod_reclamo)
                        ->modalWidth('5xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(fn (Reclamo $record) => view('filament.reclamos.view-modal', [
                            'record' => $record,
                        ])),
                 //   Tables\Actions\EditAction::make(),
                   // Tables\Actions\DeleteAction::make(),
                ]),
            ]);
           /* ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_reclamo', 'desc');*/
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
            'index' => Pages\ListReclamos::route('/'),
            'create' => Pages\CreateReclamo::route('/create'),
            'view' => Pages\ViewReclamo::route('/{record}'),
            'edit' => Pages\EditReclamo::route('/{record}/edit'),
        ];
    }

}
