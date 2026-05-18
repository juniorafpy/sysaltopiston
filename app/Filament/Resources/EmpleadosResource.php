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
                                "{$record->nombre_completo}"
                            )
                            ->searchable(['nombres', 'apellidos', 'nro_documento'])
                            ->preload()
                            ->required()
                            ->unique(
                                table: 'empleados',
                                column: 'cod_persona',
                                ignorable: fn ($record) => $record,
                            )
                            ->validationMessages([
                                'unique' => 'Esta persona ya está registrada como empleado.',
                            ])
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(100)
                            ->helperText('Email corporativo del empleado')
                            ->columnSpan(2),

                        Forms\Components\Select::make('cod_cargo')
                            ->label('Cargo')
                            ->relationship('cargo', 'descripcion')
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fec_ingreso')
                            ->label('Fecha de Ingreso')
                            ->default(now())
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Desactivar para dar de baja al empleado')
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_empleado')
                    ->label('Código'),

                Tables\Columns\TextColumn::make('persona.nombre_completo')
                    ->label('Nombre Completo')
                    ->searchable(['personas.nombres', 'personas.apellidos']),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('fec_Ingreso')
                    ->label('Fecha de Ingreso')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('cargo.descripcion')
                    ->label('Cargo')
                    ->badge()
                    ->color('info')
                    ->default('Sin cargo'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
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
            ->defaultSort('fec_alta', 'desc');
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
