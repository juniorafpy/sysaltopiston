<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrdenServicio extends ViewRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
