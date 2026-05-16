<?php

namespace App\Filament\Resources\TipoPromocionResource\Pages;

use App\Filament\Resources\TipoPromocionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoPromocions extends ListRecords
{
    protected static string $resource = TipoPromocionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
