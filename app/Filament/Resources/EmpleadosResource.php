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
                            ->searchable(['nombre', 'apellido', 'nro_documento'])
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

                        Forms\Components\DatePicker::make('fec_alta')
                            ->label('Fecha de Alta')
                            ->default(now())
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        Forms\Components\TextInput::make('cod_cargo')
                            ->label('Código Cargo')
                            ->numeric()
                            ->helperText('Código del cargo/puesto del empleado'),
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
                    ->searchable(['personas.nombre', 'personas.apellido'])
                    ->sortable()
                    ->description(fn (Empleados $record): string =>
                        $record->persona->nro_documento ?? ''
                    ),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Alias')
                    ->searchable()
                    ->default('Sin alias')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('persona.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cod_cargo')
                    ->label('Cargo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'create' => Pages\CreateEmpleados::route('/create'),
            'view' => Pages\ViewEmpleados::route('/{record}'),
            'edit' => Pages\EditEmpleados::route('/{record}/edit'),
        ];
    }
}
