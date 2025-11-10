<?php

namespace App\Filament\Resources\OrdenCompraCabeceraResource\Pages;

use App\Filament\Resources\OrdenCompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenCompraCabeceras extends ListRecords
{
    protected static string $resource = OrdenCompraCabeceraResource::class;
     protected static ?string $title = 'Listado Ordenes de Compra';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Crear Orden Compra'),
        ];
    }
}
