<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use App\Filament\Resources\CompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompraCabeceras extends ListRecords
{
    protected static string $resource = CompraCabeceraResource::class;
protected static ?string $title = 'Listado Facturas de Compra';
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
