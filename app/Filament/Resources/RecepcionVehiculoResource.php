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
use App\Models\Vehiculo;
use App\Models\Marcas;
use App\Models\Modelos;

class RecepcionVehiculoResource extends Resource
{
    protected static ?string $model = RecepcionVehiculo::class;

    protected static ?string $navigationGroup = 'Servicios';
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
                            Forms\Components\Select::make('cliente_id')
                                ->relationship('cliente', 'nombres')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('vehiculo_id')
                                ->label('Chapa (Matrícula)')
                                ->relationship('vehiculo', 'matricula')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('matricula')
                                        ->label('Chapa (Matrícula)')
                                        ->required()
                                        ->unique(table: Vehiculo::class, column: 'matricula'),
                                    Forms\Components\Select::make('marca_id')
                                        ->label('Marca')
                                        ->options(Marcas::all()->pluck('descripcion', 'cod_marca'))
                                        ->searchable()
                                        ->required(),
                                    Forms\Components\Select::make('modelo_id')
                                        ->label('Modelo')
                                        ->options(Modelos::all()->pluck('descripcion', 'cod_modelo'))
                                        ->searchable()
                                        ->required(),
                                    Forms\Components\TextInput::make('anio')
                                        ->label('Año')
                                        ->numeric()
                                        ->minValue(1900)
                                        ->maxValue(date('Y') + 1)
                                        ->required(),
                                    Forms\Components\TextInput::make('color')
                                        ->label('Color')
                                        ->maxLength(50),
                                ])
                                ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                    $data['cliente_id'] = $get('cliente_id');
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

                                Forms\Components\Select::make('empleado_id')
                                ->relationship('empleado', 'nombre')
                                ->searchable()
                                    ->label('Mecánico asignado')
                                ->placeholder('Asignar un mecánico'),

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
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Checkbox::make('inventario.extintor')
                                        ->label('Extintor')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.valija')
                                        ->label('Valija')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.rueda_auxilio')
                                        ->label('Rueda auxilio')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.gato')
                                        ->label('Gato')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.llave_ruedas')
                                        ->label('Llave ruedas')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.triangulos_seguridad')
                                        ->label('Triángulos')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.botiquin')
                                        ->label('Botiquín')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.manual_vehiculo')
                                        ->label('Manual')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.llave_repuesto')
                                        ->label('Llave repuesto')
                                        ->inline(),
                                    Forms\Components\Checkbox::make('inventario.radio_estereo')
                                        ->label('Radio')
                                        ->inline(),
                                ]),
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
                    ])->columnSpan(1),
                ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombres')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehiculo.matricula')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_recepcion')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('empleado.nombre')
                ->label('Mecánico')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('registrar_diagnostico')
                    ->label('Diagnóstico')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn ($record) => \App\Filament\Resources\DiagnosticoResource::getUrl('create', ['recepcion_id' => $record->id])),
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
