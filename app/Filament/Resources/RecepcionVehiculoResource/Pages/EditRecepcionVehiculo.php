<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecepcionVehiculo extends EditRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
