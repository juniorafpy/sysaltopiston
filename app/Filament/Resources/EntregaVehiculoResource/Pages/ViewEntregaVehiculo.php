<?php

namespace App\Filament\Resources\EntregaVehiculoResource\Pages;

use App\Filament\Resources\EntregaVehiculoResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEntregaVehiculo extends ViewRecord
{
    protected static string $resource = EntregaVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
