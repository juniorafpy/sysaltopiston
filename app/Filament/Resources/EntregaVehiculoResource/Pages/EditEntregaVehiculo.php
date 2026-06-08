<?php

namespace App\Filament\Resources\EntregaVehiculoResource\Pages;

use App\Filament\Resources\EntregaVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntregaVehiculo extends EditRecord
{
    protected static string $resource = EntregaVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
