<?php

namespace App\Filament\Resources;

use Closure;
use App\Models\Ruc;
use Filament\Forms;
use Filament\Tables;
use App\Models\Personas;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PersonasResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonasResource\RelationManagers;

class PersonasResource extends Resource
{
    protected static ?string $model = Personas::class;

    protected static ?string $navigationGroup = 'Definiciones';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationBadge = '<span class="group-hover:text-blue-500 transition-colors">👤</span>';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(10) // Dividimos el grid en 8 columnas
                ->schema([
                    // Primera Sección: Checkboxes y Número de Documento
                    Forms\Components\Section::make('')
                        ->schema([
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\Checkbox::make('ind_juridica')
                                        ->label('Ind. Jurídica')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $set('ind_fisica', false);
                                                $set('ind_fisica_disabled', true);
                                            } else {
                                                $set('ind_fisica_disabled', false);
                                            }
                                        }),

                                    Forms\Components\Checkbox::make('ind_fisica')
                                        ->label('Ind. Física')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $set('ind_juridica', false);
                                                $set('ind_juridica_disabled', true);
                                            } else {
                                                $set('ind_juridica_disabled', false);
                                            }
                                        }),
                                ])
                                ->columns(1)
                                ->columnSpan(1), // Ocupa 2 columnas en el grid

                            Forms\Components\TextInput::make('nro_documento')
                                ->label('Número de Documento')
                                ->required()
                                ->maxLength(20)
                                ->reactive()
                                ->extraAttributes([
                                    'onkeydown' => 'if (event.key === "Enter" ) { event.preventDefault(); }',
                                ])
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $ruc = Ruc::where('ruc', $state)->first();
                                    if ($ruc) {
                                        $nombreCompleto = $ruc->nombre;
                                        $partes = explode(',', $nombreCompleto);

                                        if (count($partes) >= 2) {
                                            $set('apellidos', trim($partes[0]));
                                            $set('nombres', trim($partes[1]));
                                        } else {
                                            $set('apellidos', trim($nombreCompleto));
                                            $set('nombres', null);
                                        }

                                        $set('div', $ruc->div);
                                        $set('razon_social', $ruc->nombre);
                                    } else {
                                        $set('apellidos', null);
                                        $set('nombres', null);
                                        $set('div', null);
                                    }
                                })
                                ->live(debounce: 500)
                                ->rule(function ($record) {
                                    return function (string $attribute, $value, Closure $fail) use ($record) {
                                        $personas = \App\Models\Personas::where('nro_documento', $value)
                                            ->where('cod_persona', '!=', optional($record)->cod_persona)
                                            ->get(); // Obtener registros en lugar de exists()

                                            Log::info('Validación nro_documento:', ['documento' => $value, 'resultado' => $personas]);


                                        if ($personas->isNotEmpty()) {
                                            $fail('El número de documento ya está registrado.');
                                        }
                                    };
                                })

                                ->columnSpan(3), // Ocupa 3 columnas en el grid

                            Forms\Components\TextInput::make('div')
                                ->label('Div')
                                ->disabled()
                                ->columnSpan(1) // Ocupa 1 columna y queda al lado del nro_documento
                                ->extraAttributes(['style' => 'margin-left: 1px;']), // Ajustar margen (opcional)

                            Forms\Components\TextInput::make('fec_alta')
                                ->default(now()->toDateTimeString())
                                ->readOnly()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('usuario_alta')->default(fn() => auth()->user()->name)->label('Usuario Alta')->columnSpan(1),

                            Forms\Components\Toggle::make('ind_activo')
                                ->label('Estado')
                                ->onColor('success') // Color cuando está activado
                                ->offColor('danger')
                                ->reactive()
                                ->formatStateUsing(fn($state) => $state === 'S') // Convierte "S" en true y "I" en false al cargar
                                ->dehydrateStateUsing(fn($state) => $state ? 'S' : 'I'),
                        ])
                        ->columns(8), // Asegura que la distribución sea flexible

                    // Segunda Sección: Información Personal
                    Forms\Components\Fieldset::make('Datos Persona Fisica')
                        ->schema([
                            Forms\Components\TextInput::make('nombres')->label('Nombres'),

                            Forms\Components\TextInput::make('apellidos')->label('Apellidos'),

                            Forms\Components\TextInput::make('razon_social')->label('Razón Social'),

                            Forms\Components\Select::make('cod_estado_civil')
                                ->label('Estado Civil') // Etiqueta para el campo
                                ->options(function () {
                                    return \App\Models\EstadoCivil::pluck('descripcion', 'cod_estado_civil'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                                })
                                ->searchable() // Permite buscar entre las opciones
                                ->required(), // Hacer que este campo sea obligatorio si es necesario

                            Forms\Components\TextInput::make('email')->label('Email')->email(),

                            Forms\Components\DatePicker::make('fec_nacimiento')->label('Nacimiento'),

                            Forms\Components\Select::make('sexo')
                                ->options([
                                    'M' => 'Masculino',
                                    'F' => 'Femenino',
                                ])
                                ->label('Sexo'),
                            Forms\Components\TextInput::make('edad')
                                ->label('Edad')
                                ->type('number') // Asegura que solo acepte números
                                ->maxLength(3)
                                ->numeric() // Valida que solo sean números
                                ->required(), // Hace que el campo sea obligatorio

                            Forms\Components\Select::make('cod_pais')
                                ->label('Pais') // Etiqueta para el campo
                                ->options(function () {
                                    return \App\Models\Pais::pluck('descripcion', 'cod_pais'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                                })
                                ->searchable() // Permite buscar entre las opciones
                                ->required(), // Hacer que este campo sea obligatorio si es necesario

                            Forms\Components\Select::make('cod_departamento')
                                ->label('Departamento')
                                ->options(function (callable $get) {
                                    $cod_pais = $get('cod_pais'); // Obtiene el país seleccionado
                                    if (!$cod_pais) {
                                        return []; // Si no hay país seleccionado, no muestra opciones
                                    }
                                    return \App\Models\Departamentos::where('cod_pais', $cod_pais)
                                    ->pluck('descripcion', 'cod_departamento');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive(),

                            Forms\Components\Select::make('cod_ciudad')
                                ->label('Ciudad')
                                ->options(function (callable $get) {
                                    $cod_departamento = $get('cod_departamento'); // Obtiene el departamento seleccionado
                                    if (!$cod_departamento) {
                                        return []; // Si no hay departamento seleccionado, no muestra ciudades
                                    }
                                    return \App\Models\Ciudad::where('cod_departamento', $cod_departamento)->pluck('descripcion', 'cod_ciudad'); // Devuelve las ciudades del departamento seleccionado
                                })
                                ->searchable()
                                ->reactive() // Habilita la reactividad
                                ->required(),

                            Forms\Components\TextInput::make('direccion')->label('Direccion')->maxLength(200),
                        ])
                        ->columns(4)
                        ->hidden(fn($get) => !$get('ind_fisica'))
                        ->reactive(), // Hace que se actualice automáticamente
                ]),

                Forms\Components\Fieldset::make('Datos Persona Juridica')
                ->schema([


                ]),


        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([Tables\Columns\TextColumn::make('nro_documento')->label('Número de Documento')->sortable()->searchable()])
            ->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
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
            'index' => Pages\ListPersonas::route('/'),
            'create' => Pages\CreatePersonas::route('/create'),
            'edit' => Pages\EditPersonas::route('/{record}/edit'),
        ];
    }
}
