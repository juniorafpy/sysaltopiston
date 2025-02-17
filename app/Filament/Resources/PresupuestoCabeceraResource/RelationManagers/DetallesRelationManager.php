<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PedidoDetalle;
use Tables\Columns\TextColumn;
use App\Models\PresupuestoDetalle;
use App\Models\PresupuestoCabecera;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class DetallesRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('cod_articulo')
                ->label('Código de Artículo')
                ->required(),

            Forms\Components\TextInput::make('cantidad')
                ->label('Cantidad')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('precio')
                ->label('Precio')
                ->numeric()
                ->required()
                ->editable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('cod_articulo')->label('Artículo'),
                Tables\Columns\TextColumn::make('cantidad')->label('Cantidad'),
                Tables\Columns\TextInputColumn::make('precio')->label('Precio'),

                ])




        ->headerActions([
                Tables\Actions\CreateAction::make(),

                Tables\Actions\Action::make('cargarDesdePedido')
                ->label('Cargar desde Pedido')

               /*     $presupuesto = PresupuestoCabecera::find($this->ownerRecord->nro_presupuesto);

                  //  dd($presupuesto);
                    if (!$presupuesto || !$presupuesto->nro_pedido_ref) {
                        Notification::make()
                            ->title('Debe seleccionar un pedido antes de cargar los detalles.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $detalles = PedidoDetalle::where('cod_pedido', $presupuesto->nro_pedido_ref)->get();

                    if ($detalles->isEmpty()) {
                        Notification::make()
                            ->title('El pedido seleccionado no tiene detalles.')
                            ->warning()
                            ->send();
                        return;
                    }

                    foreach ($detalles as $detalle) {
                        PresupuestoDetalle::create([
                            'nro_presupuesto' => $presupuesto->nro_presupuesto,
                            'cod_articulo' => $detalle->cod_articulo,
                            'cantidad' => $detalle->cantidad,
                        ]);
                    }

                    Notification::make()
                        ->title('Detalles del pedido cargados correctamente.')
                        ->success()
                        ->send();*/
                        ->action(function ($record) {
                            $presupuesto = PresupuestoCabecera::find($this->ownerRecord->nro_presupuesto);
                            $this->cargarDetallesDesdePedido($presupuesto);
                }),
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
              //  Tables\Actions\DeleteAction::make(),
              //Tables\Actions\EditAction::make()->inline()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }




    public function cargarDetallesDesdePedido($presupuesto): void
    {
        if (!$presupuesto || !$presupuesto->nro_pedido_ref) {
            Notification::make()
                ->title('Debe seleccionar un pedido antes de cargar los detalles.')
                ->danger()
                ->send();
            return;
        }

        $detalles = PedidoDetalle::where('cod_pedido', $presupuesto->nro_pedido_ref)->get();

        if ($detalles->isEmpty()) {
            Notification::make()
                ->title('El pedido seleccionado no tiene detalles.')
                ->warning()
                ->send();
            return;
        }

        foreach ($detalles as $detalle) {
            PresupuestoDetalle::create([
                'nro_presupuesto' => $presupuesto->nro_presupuesto,
                'cod_articulo' => $detalle->cod_articulo,
                'cantidad' => $detalle->cantidad,
            ]);
        }

        Notification::make()
            ->title('Detalles del pedido cargados correctamente.')
            ->success()
            ->send();
    }



}
