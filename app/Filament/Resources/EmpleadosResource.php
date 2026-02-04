<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpleadosResource\Pages;
use App\Filament\Resources\EmpleadosResource\RelationManagers;
use App\Models\Empleados;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmpleadosResource extends Resource
{
    protected static ?string $model = Empleados::class;

    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $modelLabel = 'Empleado';
    protected static ?string $pluralModelLabel = 'Empleados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Empleado')
                    ->schema([
                        Forms\Components\Select::make('cod_persona')
                            ->label('Persona')
                            ->relationship('persona', 'cod_persona')
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                "{$record->nombre_completo} - {$record->nro_documento}"
                            )
                            ->searchable(['nombres', 'apellidos', 'nro_documento'])
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nombres')
                                    ->label('Nombres')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('apellidos')
                                    ->label('Apellidos')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('razon_social')
                                    ->label('Razón Social (Opcional)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('nro_documento')
                                    ->label('Nro. Documento')
                                    ->required()
                                    ->unique('personas', 'nro_documento')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\Select::make('sexo')
                                    ->label('Sexo')
                                    ->options([
                                        'M' => 'Masculino',
                                        'F' => 'Femenino',
                                    ]),
                                Forms\Components\DatePicker::make('fec_nacimiento')
                                    ->label('Fecha Nacimiento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),
                            ])
                            ->helperText('Selecciona la persona o crea una nueva'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre/Alias (Opcional)')
                            ->maxLength(255)
                            ->helperText('Nombre alternativo o alias del empleado'),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(100)
                            ->helperText('Email corporativo del empleado'),

                        Forms\Components\DatePicker::make('fec_alta')
                            ->label('Fecha de Alta')
                            ->default(now())
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        Forms\Components\Select::make('cod_cargo')
                            ->label('Cargo')
                            ->relationship('cargo', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción del Cargo')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Textarea::make('responsabilidades')
                                    ->label('Responsabilidades')
                                    ->rows(3)
                                    ->maxLength(500),
                                Forms\Components\Select::make('area')
                                    ->label('Área')
                                    ->options([
                                        'Gerencia' => 'Gerencia',
                                        'Administrativa' => 'Administrativa',
                                        'Técnica' => 'Técnica',
                                        'Ventas' => 'Ventas',
                                        'Logística' => 'Logística',
                                        'Servicios Generales' => 'Servicios Generales',
                                    ])
                                    ->required(),
                                Forms\Components\Toggle::make('activo')
                                    ->label('Activo')
                                    ->default(true),
                            ])
                            ->createOptionModalHeading('Crear Nuevo Cargo')
                            ->helperText('Selecciona el cargo o crea uno nuevo'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Desactivar para dar de baja al empleado'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_empleado')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('persona.nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['personas.nombres', 'personas.apellidos'])
                    ->sortable()
                    ->description(fn (Empleados $record): string =>
                        $record->persona->nro_documento ?? ''
                    ),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Alias')
                    ->searchable()
                    ->default('Sin alias')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cargo.descripcion')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->default('Sin cargo'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('cod_empleado', 'desc');
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
            'index' => Pages\ListEmpleados::route('/'),
            // 'create' => Pages\CreateEmpleados::route('/create'), // Deshabilitado - usando modal en ListEmpleados
            'view' => Pages\ViewEmpleados::route('/{record}'),
            'edit' => Pages\EditEmpleados::route('/{record}/edit'),
        ];
    }
}
