<?php

namespace App\Filament\Resources\PresupuestoVentaResource\Pages;

use App\Filament\Resources\PresupuestoVentaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPresupuestoVentas extends ListRecords
{
    protected static string $resource = PresupuestoVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
