<?php

namespace App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;

use App\Filament\Resources\NotaCreditoDebitoCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotaCreditoDebitoCompra extends EditRecord
{
    protected static string $resource = NotaCreditoDebitoCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
