<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Filament\Resources\PedidoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedidoCabeceras extends ListRecords
{
    protected static string $resource = PedidoCabeceraResource::class;

     protected static ?string $title = 'Lista Pedidos de Compra';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Crear Pedido'),
        ];
    }
}
