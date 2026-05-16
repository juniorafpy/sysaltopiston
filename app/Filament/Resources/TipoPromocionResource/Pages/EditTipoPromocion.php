<?php

namespace App\Filament\Resources\TipoPromocionResource\Pages;

use App\Filament\Resources\TipoPromocionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoPromocion extends EditRecord
{
    protected static string $resource = TipoPromocionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
