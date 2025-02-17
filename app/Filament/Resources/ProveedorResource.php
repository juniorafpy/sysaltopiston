<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Proveedor;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ProveedorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProveedorResource\RelationManagers;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationGroup = 'Definiciones';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                    Select::make('cod_persona')
                        ->label('Persona')
                        ->relationship('personas_pro', 'nombres')
                        ->required(),
                    TextInput::make('usuario_alta')
                        ->default(auth()->user()->name)
                        ->disabled(),
                    DateTimePicker::make('fec_alta')
                        ->default(now())
                        ->readOnly(),
                    Toggle::make('estado')->label('Activo'),
                ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_proveedor')->sortable(),
                //Tables\Columns\TextColumn::make('personas_pro.nombres')->label('Persona'),
                TextColumn::make('personas_pro.nombre_completo')->label('Nombre Proveedor')->sortable(),
                TextColumn::make('usuario_alta')->sortable(),
                TextColumn::make('fec_alta')->dateTime(),
                BooleanColumn::make('estado')->label('Activo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

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
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
