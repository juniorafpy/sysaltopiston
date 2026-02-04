<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReclamos extends ListRecords
{
    protected static ?string $title = 'Listado Reclamos';
    protected static string $resource = ReclamoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Reclamo')
                ->createAnother(false),
        ];
    }
}
