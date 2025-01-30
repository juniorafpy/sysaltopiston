<?php

namespace App\Filament\Resources\ModelosResource\Pages;

use App\Filament\Resources\ModelosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModelos extends EditRecord
{
    protected static string $resource = ModelosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
