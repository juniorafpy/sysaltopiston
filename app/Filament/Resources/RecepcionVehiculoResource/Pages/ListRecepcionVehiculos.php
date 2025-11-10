<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use App\Models\RecepcionVehiculo;
use Filament\Actions;
use Filament\Tables\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRecepcionVehiculos extends ListRecords
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
