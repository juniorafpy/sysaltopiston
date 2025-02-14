<?php

namespace App\Filament\Resources\PersonasResource\Pages;

use App\Filament\Resources\PersonasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonas extends ListRecords
{
    protected static string $resource = PersonasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
