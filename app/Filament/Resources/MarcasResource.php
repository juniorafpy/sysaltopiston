<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarcasResource\Pages;
use App\Filament\Resources\MarcasResource\RelationManagers;
use App\Models\Marcas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MarcasResource extends Resource
{
    protected static ?string $model = Marcas::class;

    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('descripcion')
                ->label('Marca')
                    ->maxLength(50)
                    ->required(),

                Forms\Components\Toggle::make('estado')
                    ->label('Estado')
                    ->default(true)
                    ->formatStateUsing(fn ($state) => $state === 'A')
                    ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I'),

                    Forms\Components\Hidden::make('usuario_alta')
                    ->default(fn () =>auth()->user()->name)  //asigna automaticamente el usuario
                   ->label('Usuario Alta'),

                    Forms\Components\Hidden::make('fec_alta')
                    ->default(now()->toDateTimeString()), // Fecha actual,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_marca')
                ->label('Cod Marca')
                ->width('1%')
                ->alignment('center'), // Agregar la columna para 'cod_pais'
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ]),

                    Tables\Columns\TextColumn::make('usuario_alta')
                    ->searchable(),
                    Tables\Columns\TextColumn::make('fec_alta')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y')),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalHeading('Editar Marca')
                    ->modalSubmitActionLabel('Guardar'),
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
            'index' => Pages\ListMarcas::route('/'),
        ];
    }
}
