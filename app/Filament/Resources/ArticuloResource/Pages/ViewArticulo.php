<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArticulo extends ViewRecord
{
    protected static string $resource = ArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
