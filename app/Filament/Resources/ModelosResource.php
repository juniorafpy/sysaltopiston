<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModelosResource\Pages;
use App\Filament\Resources\ModelosResource\RelationManagers;
use App\Models\Modelos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModelosResource extends Resource
{
    protected static ?string $model = Modelos::class;

    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(50)
                    ->required(),
                /*Forms\Components\TextInput::make('cod_marca')
                    ->numeric(),*/
                    Forms\Components\Select::make('cod_marca')
                    ->label('Marca') // Etiqueta para el campo
                    ->options(function () {
                        return \App\Models\Marcas::pluck('descripcion', 'cod_marca'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                    })
                    ->searchable() // Permite buscar entre las opciones
                    ->required(), // Hacer que este campo sea obligatorio si es necesario
                   
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

                Tables\Columns\TextColumn::make('cod_modelo') 
                // ->label('Cod_Modelo')
                 ->width('1%')
                 ->alignment('center'), // Agregar la columna para 'cod_pais'

                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('desc_marca')
                    ->label('Marca')
                    ->getStateUsing(function ($record) {
                        return $record->marca ? $record->marca->descripcion : 'N/A';
                    })
                    ->extraAttributes(['class' => 'text-left']),
                    
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s')), 
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalHeading('Editar Modelo')
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
            'index' => Pages\ListModelos::route('/'),
        ];
    }
}
