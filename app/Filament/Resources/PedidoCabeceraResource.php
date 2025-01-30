<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Models\PedidoCabecera;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PedidoCabeceraResource extends Resource
{
    protected static ?string $model = PedidoCabecera::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('fec_pedido')
                    ->default(now()), // Fecha actual,

                Forms\Components\Select::make('cod_empleado')
                    ->relationship('ped_empleados', 'nombre') // Nombre de la relación en el modelo PedidoCabecera
                    ->searchable()
                    ->required()
                    //   ->searchable()
                    //  ->preload()
                    ->createOptionForm([
                        Forms\Components\DatePicker::make('fec_alta'),
                        Forms\Components\TextInput::make('cod_persona'),

                        Forms\Components\TextInput::make('cod_cargo'),

                        Forms\Components\TextInput::make('nombre'),
                    ]),

                Forms\Components\Hidden::make('usuario_alta')
                    ->default(fn() => auth()->user()->name)  //asigna automaticamente el usuario
                    ->label('Usuario Alta'),

                Forms\Components\Hidden::make('fec_alta')
                    ->default(now()->toDateTimeString()), // Fecha actual,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_pedido'),
                Tables\Columns\TextColumn::make('cod_empleado'),
                Tables\Columns\TextColumn::make('ped_empleados.nombre')->label('Nombre'),
                Tables\Columns\TextColumn::make('fec_pedido'),
                Tables\Columns\TextColumn::make('usuario_alta'),
                Tables\Columns\TextColumn::make('fec_alta'),

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
            \App\Filament\Resources\PedidoCabeceraResource\RelationManagers\PedidoDetalleRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedidoCabeceras::route('/'),
            'create' => Pages\CreatePedidoCabecera::route('/create'),
            'edit' => Pages\EditPedidoCabecera::route('/{record}/edit'),
        ];
    }
}
