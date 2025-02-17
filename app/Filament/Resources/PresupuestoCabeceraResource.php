<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Personas;
use Filament\Forms\Form;
use App\Models\Proveedor;
use Filament\Tables\Table;
use App\Models\PedidoCabecera;
use Filament\Resources\Resource;
use App\Models\PresupuestoCabecera;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PresupuestoCabeceraResource\Pages;
use App\Filament\Resources\PresupuestoCabeceraResource\RelationManagers;
use App\Filament\Resources\PresupuestoCabeceraResource\RelationManagers\DetallesRelationManager;

class PresupuestoCabeceraResource extends Resource
{
    protected static ?string $model = PresupuestoCabecera::class;

    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Presupuesto';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Select::make('cod_proveedor')
    ->label('Proveedor')
    ->options(
        Proveedor::with('personas_pro')->get()->pluck('personas_pro.nombre_completo', 'cod_proveedor') // Muestra nombre, guarda cod_proveedor
    )
    ->searchable()
    ->required(),

    Forms\Components\DateTimePicker::make('fec_presupuesto')->default(now())
            ->readOnly(), // Fecha actual,


Forms\Components\Select::make('nro_pedido_ref')
    ->label('Seleccionar Pedido')
    ->options(PedidoCabecera::pluck('cod_pedido', 'cod_pedido'))
    ->live() // Escucha cambios en el campo
    ->afterStateUpdated(fn ($state, callable $set) => $set('cargarDetalles', $state)),
    ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            DetallesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresupuestoCabeceras::route('/'),
            'create' => Pages\CreatePresupuestoCabecera::route('/create'),
            'edit' => Pages\EditPresupuestoCabecera::route('/{record}/edit'),
        ];
    }
}
