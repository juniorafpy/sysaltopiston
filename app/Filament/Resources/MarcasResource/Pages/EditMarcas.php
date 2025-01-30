<?php

namespace App\Filament\Resources\MarcasResource\Pages;

use App\Filament\Resources\MarcasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarcas extends EditRecord
{
    protected static string $resource = MarcasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
