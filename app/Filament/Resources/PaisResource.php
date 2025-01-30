<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaisResource\Pages;
use App\Filament\Resources\PaisResource\RelationManagers;
use App\Models\Pais;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaisResource extends Resource
{
    protected static ?string $model = Pais::class;

    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(50)
                    ->required(),
                Forms\Components\TextInput::make('gentilicio')
                    ->maxLength(20)
                    ->required(),
                Forms\Components\TextInput::make('abreviatura')
                    ->maxLength(3)
                    ->required(),
                Forms\Components\Hidden::make('usuario_alta')
                ->default(fn () =>auth()->user()->name)  //asigna automaticamente el usuario
               ->label('Usuario Alta'),
               //->disabled(),
               //->searchable(),
                Forms\Components\Hidden::make('fec_alta')
                ->default(now()) // Fecha actual,
                //->disabled(),
            ]);
 
       
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_pais') 
                ->label('Código de País')
                ->width('1%')
                ->alignment('center'), // Agregar la columna para 'cod_pais'
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gentilicio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('abreviatura')
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->disabled()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->date()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y')),
                    //->sortable(),
            ])
           
            ->actions([
                Tables\Actions\EditAction::make(),
               Tables\Actions\DeleteAction::make()
              // ->createAnother(false)
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
            'index' => Pages\ListPais::route('/'),
            'create' => Pages\CreatePais::route('/create'),
            'edit' => Pages\EditPais::route('/{record}/edit'),
        ];
    }

}
