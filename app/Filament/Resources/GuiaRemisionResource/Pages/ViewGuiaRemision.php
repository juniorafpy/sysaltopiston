<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Filament\Resources\GuiaRemisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGuiaRemision extends ViewRecord
{
    protected static string $resource = GuiaRemisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->estado !== 'N'),
        ];
    }
}
