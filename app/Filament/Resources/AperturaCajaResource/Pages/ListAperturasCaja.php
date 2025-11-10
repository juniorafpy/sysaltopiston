<?php

namespace App\Filament\Resources\AperturaCajaResource\Pages;

use App\Filament\Resources\AperturaCajaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAperturasCaja extends ListRecords
{
    protected static string $resource = AperturaCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Apertura')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
