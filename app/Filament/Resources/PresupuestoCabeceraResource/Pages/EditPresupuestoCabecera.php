<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresupuestoCabecera extends EditRecord
{
    protected static string $resource = PresupuestoCabeceraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
