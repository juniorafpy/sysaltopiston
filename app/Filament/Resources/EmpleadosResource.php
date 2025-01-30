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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('fec_alta'),
                Forms\Components\TextInput::make('cod_persona')
                    ->numeric(),
                Forms\Components\TextInput::make('cod_cargo')
                    ->numeric(),
                    Forms\Components\TextInput::make('nombre'),
                   
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fec_alta')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cod_persona')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cod_cargo')
                    ->numeric()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('nombre'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleados::route('/create'),
            'edit' => Pages\EditEmpleados::route('/{record}/edit'),
        ];
    }
}
