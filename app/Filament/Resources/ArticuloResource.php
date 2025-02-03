<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Articulo;
use Filament\Forms\Form;
use App\Models\Articulos;
use Filament\Tables\Table;
use Tables\Columns\BadgeColumn;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Resources\ArticuloResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ArticuloResource\RelationManagers;
use Filament\Forms\Components\Toggle;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulos::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                ->schema([
                    Grid::make(2)
                    ->schema([
                    TextInput::make('descripcion')
                        ->required()
                        ->maxLength(255),
                        Forms\Components\Select::make('cod_marca')
                        ->label('Marca') // Etiqueta para el campo
                        ->options(function () {
                            return \App\Models\Marcas::pluck('descripcion', 'cod_marca'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                        })
                        ->searchable() // Permite buscar entre las opciones
                        ->required(), // Hacer que este campo sea obligatorio si es necesario

                        Forms\Components\Select::make('cod_modelo')
                        ->label('Modelos') // Etiqueta para el campo
                        ->options(function () {
                            return \App\Models\Modelos::pluck('descripcion', 'cod_modelo'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                        })
                        ->searchable() // Permite buscar entre las opciones
                        ->required(), // Hacer que este campo sea obligatorio si es necesario
                    TextInput::make('precio')
                        ->numeric()
                        ->required(),
                        Forms\Components\Select::make('cod_medida')
                        ->label('Medidas') // Etiqueta para el campo
                        ->options(function () {
                            return \App\Models\Medidas::pluck('descripcion', 'cod_medida'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                        }),
                        Forms\Components\Select::make('cod_tip_articulo')
                        ->label('Tipo Articulo') // Etiqueta para el campo
                        ->options(function () {
                            return \App\Models\TipoArticulos::pluck('descripcion', 'cod_tip_articulo'); // Asumiendo que 'nombre' es el nombre de la marca y 'cod_marca' es el código
                        })
                        ->required(),
                    ]),

                    Toggle::make('activo')
                        ->default(true),
                        Grid::make(2)
                    ->schema([
                    TextInput::make('costo')
                        ->numeric()
                        ->required(),

                        TextInput::make('usuario_alta')
                    ->default(Auth::user()->name)
                    -> readonly(),
                    TextInput::make('fec_alta')
                                    ->default(now()->format('d/m/Y H:i:s'))
                                    ->readonly(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descripcion')->label('Articulos')->searchable(),

                TextColumn::make('marcas_ar.descripcion')->label('Marca'),

                TextColumn::make('modelos_ar.descripcion')->label('Modelo'),
                TextColumn::make('precio'),

                TextColumn::make('medida_ar.descripcion')->label('Medida'),

                TextColumn::make('tipo_articulo_ar.descripcion')->label('Tipo Articulo'),
                Tables\Columns\BadgeColumn::make('activo')
                ->label('Estado')
                ->formatStateUsing(fn ($state) => $state == 1 ? 'Activo' : 'Inactivo')
                ->colors([
                    'success' => '1',
                    'danger' => '2',
                ])
                ->icon(fn ($state) => $state == 1 ? 'heroicon-s-check' : 'heroicon-s-x-mark') ,
                TextColumn::make('costo'),
                TextColumn::make('usuario_alta'),

                TextColumn::make('fec_alta')
                    ->dateTime('d/m/Y H:i:s'),

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
            'index' => Pages\ListArticulos::route('/'),
            'create' => Pages\CreateArticulo::route('/create'),
            'edit' => Pages\EditArticulo::route('/{record}/edit'),
        ];
    }
}
