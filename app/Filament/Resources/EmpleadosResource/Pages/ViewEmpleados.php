<?php

namespace App\Filament\Resources\EmpleadosResource\Pages;

use App\Filament\Resources\EmpleadosResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmpleados extends ViewRecord
{
    protected static string $resource = EmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
