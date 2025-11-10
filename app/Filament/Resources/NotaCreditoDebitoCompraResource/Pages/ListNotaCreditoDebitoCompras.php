<?php

namespace App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;

use App\Filament\Resources\NotaCreditoDebitoCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaCreditoDebitoCompras extends ListRecords
{
    protected static string $resource = NotaCreditoDebitoCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
