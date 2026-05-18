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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PersonasResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonasResource\RelationManagers;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\DatePicker;

class PersonasResource extends Resource
{
    protected static ?string $model = Personas::class;

    protected static ?string $navigationGroup = 'Referenciales/Servicios';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Personas';
    protected static ?string $modelLabel = 'Persona';
    protected static ?string $pluralModelLabel = 'Personas';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                // PASO 1: Tipo de Persona e Identificación
                Forms\Components\Wizard\Step::make('Identificación')
                    ->description('Seleccione el tipo de persona e ingrese el documento')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('tipo_persona')
                                    ->label('Tipo de Persona')
                                    ->options([
                                        'F' => 'Persona Física',
                                        'J' => 'Persona Jurídica',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('nro_documento')
                                    ->label('Número de Documento (CI/RUC)')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('Ingrese CI o RUC...')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, callable $set, $get, $component) {
                                        if (!$state) return;

                                        $ruc = Ruc::where('ruc', $state)->first();
                                        if ($ruc) {
                                            $tipo = $get('tipo_persona');
                                            $nombreCompleto = $ruc->nombre;
                                            
                                            if ($tipo === 'J') {
                                                $set('razon_social', trim($nombreCompleto));
                                                $set('nombres', null);
                                                $set('apellidos', null);
                                            } else {
                                                $partes = explode(',', $nombreCompleto);
                                                if (count($partes) >= 2) {
                                                    $set('apellidos', trim($partes[0]));
                                                    $set('nombres', trim($partes[1]));
                                                } else {
                                                    $set('razon_social', trim($nombreCompleto));
                                                }
                                            }

                                            $set('div', $ruc->div);

                                            $component->getLivewire()->dispatch('$refresh');

                                            Notification::make()
                                                ->title('Datos encontrados en RUC')
                                                ->success()
                                                ->send();
                                        }

                                        $record = $component->getLivewire()->record;
                                        $existe = Personas::where('nro_documento', $state)
                                            ->where('cod_persona', '!=', optional($record)->cod_persona)
                                            ->first();

                                        if ($existe) {
                                            $component->getLivewire()->dispatch('documento-duplicado', id: $existe->cod_persona, documento: $state);
                                        }
                                    })
                                    ->rule(function ($record) {
                                        return function (string $attribute, $value, Closure $fail) use ($record) {
                                            $existe = Personas::where('nro_documento', $value)
                                                ->where('cod_persona', '!=', optional($record)->cod_persona)
                                                ->exists();

                                            if ($existe) {
                                                $fail('El número de documento ya está registrado.');
                                            }
                                        };
                                    })
                                    ->columnSpan(1),

                                TextInput::make('div')
                                    ->label('DV (Dígito Verificador)')
                                    ->maxLength(2)
                                    ->extraInputAttributes(['style' => 'max-width: 4rem;'])
                                    ->placeholder('DV'),
                            ]),
                    ]),

                // PASO 2: Datos según tipo de persona
                Forms\Components\Wizard\Step::make('Datos Personales')
                    ->description('Complete la información según el tipo de persona')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('nombres')
                                    ->label('Nombres')
                                    ->required(fn ($get) => $get('tipo_persona') === 'F')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->maxLength(100)
                                    ->placeholder('Nombres completos')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => mb_strtoupper((string) $state))
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('apellidos')
                                    ->label('Apellidos')
                                    ->required(fn ($get) => $get('tipo_persona') === 'F')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->maxLength(100)
                                    ->placeholder('Apellidos completos')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => mb_strtoupper((string) $state))
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('razon_social')
                                    ->label('Razón Social')
                                    ->required(fn ($get) => $get('tipo_persona') === 'J')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'F')
                                    ->maxLength(200)
                                    ->placeholder('Nombre de la empresa')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn ($state) => mb_strtoupper((string) $state))
                                    ->live()
                                    ->columnSpan(2),

                                Select::make('cod_estado_civil')
                                    ->label('Estado Civil')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->relationship('estadoCivil', 'descripcion')
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Seleccione...')
                                    ->columnSpan(1),

                                TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->placeholder('ejemplo@correo.com')
                                    ->columnSpan(1),

                                DatePicker::make('fec_nacimiento')
                                    ->label('Fecha de Nacimiento')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->placeholder('DD/MM/AAAA')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (blank($state)) {
                                            $set('edad_calculada', null);
                                            return;
                                        }

                                        try {
                                            $edad = \Carbon\Carbon::parse($state)->age;
                                            $set('edad_calculada', $edad);
                                        } catch (\Throwable $th) {
                                            $set('edad_calculada', null);
                                        }
                                    })
                                    ->dehydrateStateUsing(fn ($state) => filled($state)
                                        ? \Carbon\Carbon::parse($state)->format('Y-m-d')
                                        : null)
                                    ->columnSpan(1),

                                TextInput::make('edad_calculada')
                                    ->label('Edad')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->suffix('años')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Select::make('sexo')
                                    ->label('Sexo')
                                    ->hidden(fn ($get) => $get('tipo_persona') === 'J')
                                    ->options([
                                        'M' => 'Masculino',
                                        'F' => 'Femenino',
                                    ])
                                    ->native(false)
                                    ->placeholder('Seleccione...')
                                    ->columnSpan(1),
                            ]),
                    ]),

                // PASO 3: Ubicación
                Forms\Components\Wizard\Step::make('Ubicación')
                    ->description('Dirección y ubicación geográfica')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Fieldset::make('Dirección')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('cod_pais')
                                            ->label('País')
                                            ->options(fn () => \App\Models\Pais::pluck('descripcion', 'cod_pais'))
                                            ->preload()
                                            ->native(false)
                                            ->required()
                                            ->default(1)
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('cod_departamento', null))
                                            ->columnSpan(1),

                                        Select::make('cod_departamento')
                                            ->label('Departamento')
                                            ->options(function (callable $get) {
                                                $cod_pais = $get('cod_pais');
                                                if (!$cod_pais) return [];
                                                return \App\Models\Departamentos::where('cod_pais', $cod_pais)
                                                    ->pluck('descripcion', 'cod_departamento');
                                            })
                                            ->preload()
                                            ->native(false)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('cod_ciudad', null))
                                            ->columnSpan(1),

                                        Select::make('cod_ciudad')
                                            ->label('Ciudad')
                                            ->options(function (callable $get) {
                                                $cod_departamento = $get('cod_departamento');
                                                if (!$cod_departamento) return [];
                                                return \App\Models\Ciudad::where('cod_departamento', $cod_departamento)
                                                    ->pluck('descripcion', 'cod_ciudad');
                                            })
                                            ->preload()
                                            ->native(false)
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('direccion')
                                            ->label('Dirección Completa')
                                            ->maxLength(200)
                                            ->placeholder('Calle, número, barrio...')
                                            ->columnSpan(3),
                                    ]),
                            ]),
                    ]),

                // PASO 4: Configuración Final
                Forms\Components\Wizard\Step::make('Configuración')
                    ->description('Estado y datos de registro')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('ind_activo')
                                    ->label('Estado Activo')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->default(true)
                                    ->helperText('Desactive para dar de baja a la persona')
                                    ->columnSpan(1),

                                TextInput::make('usuario_alta')
                                    ->label('Registrado por')
                                    ->default(fn () => auth()->user()->name)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('fec_alta')
                                    ->label('Fecha de Registro')
                                    ->default(now()->format('d/m/Y H:i'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                            ]),
                    ]),
            ])
            ->columnSpanFull()
            ->skippable()
            ->persistStepInQueryString()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_persona')
                    ->label('#'),

                Tables\Columns\TextColumn::make('nro_documento')
                    ->label('CI/RUC')
                    ->icon('heroicon-o-identification')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['nombres', 'apellidos', 'razon_social'])
                    ->icon(fn ($record) => $record->ind_fisica ? 'heroicon-o-user' : 'heroicon-o-building-office-2')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope'),


                Tables\Columns\IconColumn::make('ind_activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),


            ])
            ->defaultSort('fec_alta', 'desc')

            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->icon('heroicon-m-eye')
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),

                    
                ])
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
            'index' => Pages\ListPersonas::route('/'),
            'create' => Pages\CreatePersonas::route('/create'),
            'edit' => Pages\EditPersonas::route('/{record}/edit'),
        ];
    }
}
