<?php

namespace App\Filament\Resources\CompraCabeceraResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompraDetalleRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cod_articulo')
                    ->relationship('articulo', 'descripcion')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('cantidad')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('precio_unitario')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cod_articulo')
            ->columns([
                Tables\Columns\TextColumn::make('articulo.descripcion'),
                Tables\Columns\TextColumn::make('cantidad'),
                Tables\Columns\TextColumn::make('precio_unitario'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
