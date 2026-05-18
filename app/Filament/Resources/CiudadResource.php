<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CiudadResource\Pages;
use App\Models\Ciudad;
use App\Models\Departamentos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CiudadResource extends Resource
{
    protected static ?string $model = Ciudad::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?string $navigationLabel = 'Mantener Ciudad';
    protected static ?string $modelLabel = 'Ciudad';
    protected static ?string $pluralModelLabel = 'Ciudades';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Ciudad')
                    ->schema([
                        Forms\Components\TextInput::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('descripcion', strtoupper($state))),
                        Forms\Components\Select::make('cod_departamento')
                            ->label('Departamento')
                            ->relationship('departamento', 'descripcion')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('usuario_alta')
                            ->label('Usuario Alta')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('fec_alta')
                            ->label('Fecha Alta')
                            ->default(now())
                            ->disabled()
                            ->dehydrated()
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('estado')
                            ->label('Estado')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'A')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_ciudad')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departamento.descripcion')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario Alta'),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalSubmitActionLabel('Guardar')
                    ->successNotificationTitle(null)
                    ->after(function ($record, $action) {
                        $action->getLivewire()->dispatch('swal:success', message: 'Ciudad actualizada exitosamente.');
                    }),
            ])
            
            ->defaultSort('descripcion', 'asc');
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
            'index' => Pages\ListCiudads::route('/'),
        ];
    }
}
