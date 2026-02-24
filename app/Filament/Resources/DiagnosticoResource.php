<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiagnosticoResource\Pages;
use App\Filament\Resources\PresupuestoVentaResource;
use App\Models\Diagnostico;
use App\Models\RecepcionVehiculo;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

class DiagnosticoResource extends Resource
{
    protected static ?string $model = Diagnostico::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?int $navigationSort = 8;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Section::make('Referencia de recepción')
                            ->icon('heroicon-o-identification')
                            ->description('Busca la recepción del vehículo y revisa los datos claves antes de registrar el diagnóstico.')
                            ->schema([
                                Forms\Components\Select::make('recepcion_vehiculo_id')
                                    ->label('Recepción vinculada')
                                    ->relationship(
                                        name: 'recepcionVehiculo',
                                        titleAttribute: 'id',
                                        modifyQueryUsing: fn ($query) => $query->with(['vehiculo', 'cliente.persona'])
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (?RecepcionVehiculo $record) => $record ? sprintf('#%s · %s · %s',
                                        $record->id,
                                        $record->vehiculo?->matricula ?? 'Sin chapa',
                                        $record->cliente?->nombre_completo ?? 'Sin cliente'
                                    ) : null)
                                    ->searchable(['id', 'motivo_ingreso'])
                                    ->preload()
                                    ->reactive()
                                    ->placeholder('Selecciona o busca por ID o motivo')
                                    ->default(fn () => request()->get('recepcion_id'))
                                    ->disabled(fn () => request()->has('recepcion_id'))
                                    ->required(),

                                Forms\Components\DateTimePicker::make('fecha_diagnostico')
                                    ->label('Fecha de Diagnóstico')
                                    ->default(now())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('vehiculo')
                                            ->label('Chapa / Vehículo')
                                            ->content(fn (Get $get) => static::resolveRecepcion($get('recepcion_vehiculo_id'))?->vehiculo?->matricula ?? '—')
                                          ->extraAttributes([
                                                'class' => 'p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20',
                                            ]),

                                        Forms\Components\Placeholder::make('marca')
                                            ->label('Marca')
                                            ->content(fn (Get $get) => static::resolveRecepcion($get('recepcion_vehiculo_id'))?->vehiculo?->marca?->descripcion ?? '—')
                                            ->extraAttributes([
                                                'class' => 'p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20',
                                            ]),

                                        Forms\Components\Placeholder::make('modelo')
                                            ->label('Modelo')
                                            ->content(fn (Get $get) => static::resolveRecepcion($get('recepcion_vehiculo_id'))?->vehiculo?->modelo?->descripcion ?? '—')
                                            ->extraAttributes([
                                                'class' => 'p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20',
                                            ]),

                                        Forms\Components\Placeholder::make('cliente')
                                            ->label('Cliente')
                                            ->content(function (Get $get) {
                                                $recepcion = static::resolveRecepcion($get('recepcion_vehiculo_id'));
                                                if ($recepcion && $recepcion->cliente) {
                                                    return $recepcion->cliente->nombre_completo;
                                                }
                                                return '—';
                                            })
                                            ->extraAttributes([
                                                'class' => 'p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20',
                                            ]),

                                        Forms\Components\Placeholder::make('motivo')
                                            ->label('Motivo del ingreso')
                                            ->content(fn (Get $get) => static::resolveRecepcion($get('recepcion_vehiculo_id'))?->motivo_ingreso ?? '—')
                                            ->columnSpan(2)
                                            ->extraAttributes([
                                                'class' => 'p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20',
                                            ]),
                                    ]),
                            ])
                            ->columns([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Section::make('Diagnóstico y seguimiento')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\Textarea::make('diagnostico_mecanico')
                                    ->label('Diagnóstico del mecánico')
                                    ->placeholder('Describe el problema encontrado, observaciones técnicas y las pruebas realizadas.')
                                    ->autosize()
                                    ->maxLength(2000)
                                    ->required(),

                                Forms\Components\Textarea::make('observaciones')
                                    ->label('Notas adicionales')
                                    ->placeholder('Anota recomendaciones, repuestos requeridos o comentarios para el asesor de servicio.')
                                    ->autosize()
                                    ->maxLength(2000),
                            ])
                            ->columns(1),
                    ])->columnSpan(2),

                    Group::make()->schema([
                        Section::make('Información del Sistema')
                            ->schema([
                                Forms\Components\Hidden::make('cod_sucursal'),
                                Forms\Components\TextInput::make('nombre_sucursal')
                                    ->label('Sucursal')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Hidden::make('usuario_alta'),
                                Forms\Components\TextInput::make('nombre_usuario')
                                    ->label('Usuario Alta')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Placeholder::make('fec_alta')
                                    ->label('Fecha Alta')
                                    ->content(fn (?Diagnostico $record) => ($record && $record->fec_alta) ? $record->fec_alta->format('d/m/Y H:i') : now()->format('d/m/Y H:i')),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'recepcionVehiculo.vehiculo',
                'recepcionVehiculo.cliente.persona',
                'recepcionVehiculo.empleado.persona',
                'empleado.persona'
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recepcionVehiculo.vehiculo.matricula')
                    ->label('Chapa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recepcionVehiculo.cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recepcionVehiculo.empleado_id')
                    ->label('Mecánico')
                    ->formatStateUsing(function ($state, Diagnostico $record) {
                        $empleado = $record->recepcionVehiculo?->empleado;

                        if (!$empleado) {
                            return '-';
                        }

                        if ($empleado->persona) {
                            if ($empleado->persona->razon_social) {
                                return $empleado->persona->razon_social;
                            }

                            $nombre = trim(($empleado->persona->nombres ?? '') . ' ' . ($empleado->persona->apellidos ?? ''));
                            if ($nombre !== '') {
                                return $nombre;
                            }
                        }

                        return $empleado->nombre ?? "Empleado #{$state}";
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_diagnostico')
                    ->dateTime('d/m/Y H:i')
                    ->label('Fecha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente a presupuesto' => 'warning',
                        'Completado' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver'),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('presupuesto')
                        ->label('Generar presupuesto')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('primary')
                        ->url(fn (Diagnostico $record) => PresupuestoVentaResource::getUrl('create', ['diagnostico_id' => $record->id]))
                        ->openUrlInNewTab(),
                        Tables\Actions\Action::make('imprimir')
                        ->label('Imprimir')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn (Diagnostico $record) => route('diagnosticos.imprimir', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn ($record) => route('diagnosticos.pdf', $record))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiagnosticos::route('/'),
            'create' => Pages\CreateDiagnostico::route('/create'),
            'edit' => Pages\EditDiagnostico::route('/{record}/edit'),
        ];
    }

    protected static function resolveRecepcion(null|int|string $id): ?RecepcionVehiculo
    {
        static $cache = [];

        if (!$id) {
            return null;
        }

        $id = (int) $id;

        if (! array_key_exists($id, $cache)) {
            $cache[$id] = RecepcionVehiculo::with([
                'vehiculo.marca',
                'vehiculo.modelo',
                'cliente.persona',
            ])->find($id);
        }

        return $cache[$id];
    }
}
