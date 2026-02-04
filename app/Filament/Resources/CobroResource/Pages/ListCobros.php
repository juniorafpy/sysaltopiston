<?php

namespace App\Filament\Resources\CobroResource\Pages;

use App\Filament\Resources\CobroResource;
use Filament\Resources\Pages\ListRecords;

class ListCobros extends ListRecords
{
    protected static string $resource = CobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->createAnother(false),
        ];
    }
}
