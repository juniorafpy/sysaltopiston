<?php

namespace App\Filament\Resources\EmpleadosResource\Pages;

use App\Filament\Resources\EmpleadosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmpleados extends ListRecords
{
    protected static string $resource = EmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
