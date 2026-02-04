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
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PersonasResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonasResource\RelationManagers;

class PersonasResource extends Resource
{
    protected static ?string $model = Personas::class;

    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Personas';
    protected static ?string $modelLabel = 'Persona';
    protected static ?string $pluralModelLabel = 'Personas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // SECCIÓN 1: Tipo de Persona y Documento
            Section::make('Identificación')
                ->description('Seleccione el tipo de persona e ingrese el documento')
                ->icon('heroicon-o-identification')
                ->collapsible()
                ->schema([
                    Grid::make(4)
                        ->schema([
                            // Tipo de Persona
                            Checkbox::make('ind_juridica')
                                ->label('Persona Jurídica')
                                ->reactive()
                                ->inline(false)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $set('ind_fisica', false);
                                    }
                                })
                                ->columnSpan(1),

                            Checkbox::make('ind_fisica')
                                ->label('Persona Física')
                                ->reactive()
                                ->inline(false)
                                ->default(true)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $set('ind_juridica', false);
                                    }
                                })
                                ->columnSpan(1),

                            // Número de Documento con búsqueda RUC
                            TextInput::make('nro_documento')
                                ->label('Número de Documento')
                                ->required()
                                ->maxLength(20)
                                ->placeholder('Ingrese CI o RUC...')
                                ->reactive()
                                ->live(debounce: 500)
                                ->helperText('Presione Enter para buscar en RUC')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) return;

                                    $ruc = Ruc::where('ruc', $state)->first();
                                    if ($ruc) {
                                        $nombreCompleto = $ruc->nombre;
                                        $partes = explode(',', $nombreCompleto);

                                        if (count($partes) >= 2) {
                                            $set('apellidos', trim($partes[0]));
                                            $set('nombres', trim($partes[1]));
                                        } else {
                                            $set('razon_social', trim($nombreCompleto));
                                        }

                                        $set('div', $ruc->div);

                                        Notification::make()
                                            ->title('Datos encontrados en RUC')
                                            ->success()
                                            ->send();
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
                                ->label('DV')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Auto')
                                ->columnSpan(1),
                        ]),
                ]),

            // SECCIÓN 2: Datos Persona Física
            Section::make('Datos de Persona Física')
                ->description('Complete la información personal')
                ->icon('heroicon-o-user')
                ->collapsible()
                ->collapsed(fn ($get) => $get('ind_juridica'))
                ->hidden(fn ($get) => $get('ind_juridica'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('nombres')
                                ->label('Nombres')
                                ->required(fn ($get) => $get('ind_fisica'))
                                ->maxLength(100)
                                ->placeholder('Nombres completos')
                                ->columnSpan(1),

                            TextInput::make('apellidos')
                                ->label('Apellidos')
                                ->required(fn ($get) => $get('ind_fisica'))
                                ->maxLength(100)
                                ->placeholder('Apellidos completos')
                                ->columnSpan(1),

                            TextInput::make('razon_social')
                                ->label('Razón Social')
                                ->maxLength(200)
                                ->placeholder('Nombre comercial (opcional)')
                                ->columnSpan(1),

                            Select::make('cod_estado_civil')
                                ->label('Estado Civil')
                                ->options(fn () => \App\Models\EstadoCivil::pluck('descripcion', 'cod_estado_civil'))
                                ->searchable()
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
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->maxDate(now()->subYears(18))
                                ->helperText('Debe ser mayor de 18 años')
                                ->columnSpan(1),

                            Select::make('sexo')
                                ->label('Sexo')
                                ->options([
                                    'M' => 'Masculino',
                                    'F' => 'Femenino',
                                ])
                                ->native(false)
                                ->placeholder('Seleccione...')
                                ->columnSpan(1),

                            TextInput::make('edad')
                                ->label('Edad')
                                ->numeric()
                                ->minValue(18)
                                ->maxValue(120)
                                ->suffix('años')
                                ->columnSpan(1),
                        ]),

                    Grid::make(3)
                        ->schema([
                            Select::make('cod_pais')
                                ->label('País')
                                ->options(fn () => \App\Models\Pais::pluck('descripcion', 'cod_pais'))
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->default(1)
                                ->reactive()
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
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->reactive()
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
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->reactive()
                                ->columnSpan(1),

                            TextInput::make('direccion')
                                ->label('Dirección')
                                ->maxLength(200)
                                ->placeholder('Calle, número, barrio...')
                                ->columnSpan(3),
                        ]),
                ]),

            // SECCIÓN 3: Datos Persona Jurídica
            Section::make('Datos de Persona Jurídica')
                ->description('Complete la información de la empresa')
                ->icon('heroicon-o-building-office-2')
                ->collapsible()
                ->collapsed(fn ($get) => $get('ind_fisica'))
                ->hidden(fn ($get) => $get('ind_fisica'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('razon_social')
                                ->label('Razón Social')
                                ->required(fn ($get) => $get('ind_juridica'))
                                ->maxLength(200)
                                ->placeholder('Nombre de la empresa')
                                ->columnSpan(2),

                            TextInput::make('email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->placeholder('contacto@empresa.com')
                                ->columnSpan(1),

                            TextInput::make('direccion')
                                ->label('Dirección')
                                ->maxLength(200)
                                ->placeholder('Dirección de la empresa')
                                ->columnSpan(1),
                        ]),
                ]),

            // SECCIÓN 4: Auditoría
            Section::make('Información de Registro')
                ->description('Datos de auditoría del sistema')
                ->icon('heroicon-o-clock')
                ->collapsed()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('usuario_alta')
                                ->label('Registrado por')
                                ->default(fn () => auth()->user()->name)
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('fec_alta')
                                ->label('Fecha de Registro')
                                ->default(now()->toDateTimeString())
                                ->disabled()
                                ->dehydrated(false),

                            Toggle::make('ind_activo')
                                ->label('Estado Activo')
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false)
                                ->default(true)
                                ->formatStateUsing(fn ($state) => $state === 'S')
                                ->dehydrateStateUsing(fn ($state) => $state ? 'S' : 'I'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_persona')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('nro_documento')
                    ->label('CI/RUC')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Documento copiado')
                    ->icon('heroicon-o-identification')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['nombres', 'apellidos', 'razon_social'])
                    ->sortable()
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

                Tables\Columns\TextColumn::make('tipo_persona')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->ind_fisica ? 'Física' : 'Jurídica')
                    ->color(fn ($record) => $record->ind_fisica ? 'info' : 'warning')
                    ->icon(fn ($record) => $record->ind_fisica ? 'heroicon-o-user' : 'heroicon-o-building-office-2'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ciudad.descripcion')
                    ->label('Ciudad')
                    ->sortable()
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('ind_activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->ind_activo === 'S')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),
            ])
            ->defaultSort('cod_persona', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_persona')
                    ->label('Tipo de Persona')
                    ->options([
                        'fisica' => 'Física',
                        'juridica' => 'Jurídica',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'fisica') {
                            return $query->where('ind_fisica', true);
                        } elseif ($data['value'] === 'juridica') {
                            return $query->where('ind_juridica', true);
                        }
                    })
                    ->native(false),

                Tables\Filters\TernaryFilter::make('ind_activo')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos')
                    ->queries(
                        true: fn (Builder $query) => $query->where('ind_activo', 'S'),
                        false: fn (Builder $query) => $query->where('ind_activo', 'I'),
                    )
                    ->native(false),
            ])
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

                    Tables\Actions\Action::make('toggle_estado')
                        ->label(fn ($record) => $record->ind_activo === 'S' ? 'Desactivar' : 'Activar')
                        ->icon(fn ($record) => $record->ind_activo === 'S' ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                        ->color(fn ($record) => $record->ind_activo === 'S' ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $nuevoEstado = $record->ind_activo === 'S' ? 'I' : 'S';
                            $record->update(['ind_activo' => $nuevoEstado]);

                            Notification::make()
                                ->title($nuevoEstado === 'S' ? 'Persona activada' : 'Persona desactivada')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['ind_activo' => 'S']);
                            Notification::make()
                                ->title('Personas activadas')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['ind_activo' => 'I']);
                            Notification::make()
                                ->title('Personas desactivadas')
                                ->warning()
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
            'index' => Pages\ListPersonas::route('/'),
            'create' => Pages\CreatePersonas::route('/create'),
            'edit' => Pages\EditPersonas::route('/{record}/edit'),
        ];
    }
}
