<?php

namespace App\Filament\Resources\PedidoCabeceraResource\RelationManagers;

use App\Models\Articulos;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PedidoDetalle;
use Filament\Forms\FormsComponent;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;
use PhpParser\Node\Stmt\Label;

class PedidoDetalleRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    public static function deleteConfirmation(): string
    {
        return '¿Estás seguro de que deseas eliminar este Articulo? Esta acción no se puede deshacer.';
    }



    public function form(Form $form): Form
    {
        return $form
            ->schema([
              Forms\Components\Select::make('cod_articulo')
                ->relationship('articulos_det', 'descripcion')
                    ->required()
                    ->searchable()
                    ->preload()
                   ->afterStateUpdated(function ($state, callable $set) {
                        // Buscar el artículo seleccionado y asignar su costo
                        $articulo = Articulos::find($state);
                        if ($articulo) {
                            $set('precio', $articulo->precio); // Asigna el costo automáticamente
                        }
                    }),


                Forms\Components\TextInput::make('cantidad')
                ->required()
                ->numeric()
                 ->minValue(1),

                   //Forms\Components\TextInput::make('Costo')->default(Articulos::),
        /*    Forms\Components\TextInput::make('precio')
            ->numeric()
            ->required()
            ->disabled() // Opcional: Evita que el usuario lo edite manualmente
            ->dehydrated(), // Asegura que se guarde en la base de datos
*/
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('articulos')
            ->columns([
                Tables\Columns\TextColumn::make('cod_articulo'),
                Tables\Columns\TextColumn::make('articulos_det.descripcion')->label('Desc Articulo'),
                Tables\Columns\TextColumn::make('cantidad'),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->sortable(),
               // Tables\Columns\TextColumn::make('precio'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Carga Articulo'),

                // Tables\Actions\CreateAction::make()->modal(false), // 🔴 Desactiva modal para agregar
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()  // Acción personalizada para eliminar un solo artículo
                ->label('Delete')
                ->modalHeading('Eliminar Articulo') // Personaliza el título del modal
                ->modalDescription('¿Estás seguro de que deseas eliminar este articulo del pedido? Esta acción no se puede deshacer.') // Personaliza el mensaje de confirmación
                ->modalButton('Sí, eliminar') // Personaliza el botón de confirmación






            ])
        ->bulkActions([
                //
            ]);
    }


}
