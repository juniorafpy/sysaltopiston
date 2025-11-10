<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecepcionVehiculo extends CreateRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }
}
