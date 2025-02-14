<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;



use Filament\Actions;

use Filament\Resources\Pages\ListRecords;

use App\Filament\Resources\PedidoCabeceraResource;



class ListPedidoCabeceras extends ListRecords
{
    protected static string $resource = PedidoCabeceraResource::class;

    protected static ?string $title = 'Listado de Pedidos';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Crear Pedido'),

        ];
    }
}
