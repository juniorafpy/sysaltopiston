<?php

namespace App\Filament\Resources;
use App\Filament\Resources\RecepcionVehiculoResource\Pages;
use App\Filament\Resources\RecepcionVehiculoResource\RelationManagers;
use App\Models\RecepcionVehiculo;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Filament\Forms\Get;
use App\Models\Vehiculo;
use App\Models\Marcas;
use App\Models\Modelos;
use App\Models\Color;
use App\Models\TipoServicio;
use App\Models\InventarioServicio;

class RecepcionVehiculoResource extends Resource
{
    protected static ?string $model = RecepcionVehiculo::class;

    protected static ?string $navigationGroup = 'Gestión Servicios';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $modelLabel = 'Recepción de Vehículo';
    protected static ?string $pluralModelLabel = 'Recepciones de Vehículos';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Group::make()->schema([
                        Section::make('Datos de la Recepción')->schema([
                            Forms\Components\Select::make('cod_cliente')
                                ->label('Cliente')
                                ->options(function () {
                                    return \App\Models\Cliente::with('persona')
                                        ->get()
                                        ->pluck('nombre_completo', 'cod_cliente');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('vehiculo_id', null);
                                })
                                ->required(),

                            Forms\Components\Select::make('vehiculo_id')
                                ->label('Chapa (Matrícula)')
                                ->relationship(
                                    name: 'vehiculo',
                                    modifyQueryUsing: fn ($query, Get $get) =>
                                        $query->with(['modelo'])
                                            ->when($get('cod_cliente'), fn ($q, $clienteId) =>
                                                $q->where('cod_cliente', $clienteId)
                                            )
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record->matricula . ' - ' . ($record->modelo->descripcion ?? 'Sin modelo')
                                )
                                ->searchable(['matricula'])
                                ->preload()
                                ->disabled(fn (Get $get) => !$get('cod_cliente'))
                                ->helperText(fn (Get $get) => !$get('cod_cliente')
                                    ? 'Primero seleccione un cliente'
                                    : 'Seleccione un vehículo o cree uno nuevo')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('matricula')
                                        ->label('Chapa (Matrícula)')
                                        ->required()
                                        ->unique(table: Vehiculo::class, column: 'matricula'),
                                    Forms\Components\Select::make('marca_id')
                                        ->label('Marca')
                                        ->options(Marcas::all()->pluck('descripcion', 'cod_marca'))
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('modelo_id', null);
                                        })
                                        ->required(),
                                    Forms\Components\Select::make('modelo_id')
                                        ->label('Modelo')
                                        ->options(function (Get $get) {
                                            $marcaId = $get('marca_id');
                                            if (!$marcaId) {
                                                return [];
                                            }
                                            return Modelos::where('cod_marca', $marcaId)
                                                ->pluck('descripcion', 'cod_modelo');
                                        })
                                        ->searchable()
                                        ->disabled(fn (Get $get) => !$get('marca_id'))
                                        ->helperText(fn (Get $get) => !$get('marca_id')
                                            ? 'Primero seleccione una marca'
                                            : null)
                                        ->required(),
                                    Forms\Components\TextInput::make('anio')
                                        ->label('Año')
                                        ->numeric()
                                        ->minValue(1900)
                                        ->maxValue(date('Y') + 1)
                                        ->required(),
                                    Forms\Components\Select::make('color_id')
                                        ->label('Color')
                                        ->options(Color::all()->pluck('descripcion', 'cod_color'))
                                        ->searchable()
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data, Get $get): int {
                                    $data['cod_cliente'] = $get('cod_cliente');
                                    $vehiculo = Vehiculo::create($data);
                                    return $vehiculo->id;
                                })
                                ->required(),
                           /* Forms\Components\DateTimePicker::make('fecha_recepcion')
                                ->default(now())
                                ->required(),*/
                            Forms\Components\DateTimePicker::make('fecha_recepcion')
                                ->default(now())
                                ->required(),
                            Forms\Components\TextInput::make('kilometraje')
                                ->required()
                                ->numeric(),
                            Forms\Components\Select::make('estado')
                                ->options([
                                    'Ingresado' => 'Ingresado',
                                    'En Taller' => 'En Taller',
                                    'Finalizado' => 'Finalizado',
                                ])
                                ->default('Ingresado')
                                ->required(),

                            Forms\Components\Select::make('cod_tipo_servicio')
                                ->label('Tipo de Servicio')
                                ->placeholder('Seleccione un tipo de servicio')
                                ->options(TipoServicio::all()->pluck('descripcion', 'cod_tipo_servicio'))
                                ->searchable()
                                ->preload()
                                ->required(),

                        ])->columns(2),

                        Section::make('Motivo y Observaciones')->schema([
                            Forms\Components\Textarea::make('motivo_ingreso')
                                ->required()
                                ->columnSpanFull(),
                           /* Forms\Components\Textarea::make('observaciones')
                                ->columnSpanFull(),*/
                        ]),

                        Section::make('Inventario del Vehículo')
                            ->description('Marque los artículos que posee el vehículo al momento de la recepción')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Forms\Components\CheckboxList::make('items_inventario')
                                    ->label('Items de Inventario')
                                    ->relationship(
                                        name: 'itemsInventario',
                                        titleAttribute: 'descripcion',
                                        modifyQueryUsing: fn ($query) => $query->where('estado', 'A')->where('tipo', 'I')
                                    )
                                    ->columns(3)
                                    ->gridDirection('row')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Select::make('inventario.nivel_combustible')
                                    ->label('Nivel de combustible')
                                    ->options([
                                        'vacio' => 'Vacío (E)',
                                        '1/4' => '1/4',
                                        '2/4' => '1/2',
                                        '3/4' => '3/4',
                                        'lleno' => 'Lleno (F)',
                                    ])
                                    ->placeholder('Nivel combustible')
                                    ->columnSpan(2),
                                
                                Forms\Components\Textarea::make('inventario.observaciones_inventario')
                                    ->label('Observaciones del inventario')
                                    ->placeholder('Detalles adicionales...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->compact(),
                    ])->columnSpan(2),

                    Group::make()->schema([
                        Section::make('Información del Sistema')->schema([
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
                                ->content(fn () => now()->format('d/m/Y H:i')),
                        ]),

                        Section::make('Daños Externos')
                            ->description('Marque los daños externos del vehículo')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->schema([
                                Forms\Components\CheckboxList::make('items_danos_externos')
                                    ->label('')
                                    ->relationship(
                                        name: 'itemsInventario',
                                        titleAttribute: 'descripcion',
                                        modifyQueryUsing: fn ($query) => $query->where('estado', 'A')->where('tipo', 'E')
                                    )
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ])
                            ->compact()
                            ->collapsible(),
                    ])->columnSpan(1),
                ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) =>
                $query->with(['cliente.persona', 'vehiculo.modelo', 'empleado.persona', 'diagnosticos'])
            )
            ->columns([

            Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['cliente.persona.nombres', 'cliente.persona.apellidos', 'cliente.persona.razon_social']),
                Tables\Columns\TextColumn::make('vehiculo.matricula')
                    ->label('Vehículo')
                    ->formatStateUsing(fn ($record) =>
                        $record->vehiculo->matricula . ' - ' . ($record->vehiculo->modelo->descripcion ?? 'Sin modelo')
                    )
                    ->searchable(['vehiculo.matricula']),
                Tables\Columns\TextColumn::make('fecha_recepcion')
                    ->label('Fecha Recepción')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipoServicio.descripcion')
                    ->label('Tipo de Servicio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('Diagnóstico')
                    ->getStateUsing(function ($record) {
                        if ($record->diagnosticos && count($record->diagnosticos) > 0) {
                            return 'Diagnosticado';
                        }
                        return 'Pendiente';
                    })
                    ->color(function ($record): string {
                        if ($record->diagnosticos && count($record->diagnosticos) > 0) {
                            return 'success';
                        }
                        return 'warning';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver Detalles'),
                    /*Tables\Actions\Action::make('imprimir_comprobante')
                        ->label('Imprimir Comprobante')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn ($record) => route('recepcion-vehiculo.pdf', $record->id))
                        ->openUrlInNewTab(),*/
                    Tables\Actions\Action::make('registrar_diagnostico')
                        ->label('Registrar Diagnóstico')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->url(fn ($record) => \App\Filament\Resources\DiagnosticoResource::getUrl('create', ['recepcion_id' => $record->id])),
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
            'index'       => Pages\ListRecepcionVehiculos::route('/'),
            'create'      => Pages\CreateRecepcionVehiculo::route('/create'),
            'edit'        => Pages\EditRecepcionVehiculo::route('/{record}/edit'),
        ];
    }
}
