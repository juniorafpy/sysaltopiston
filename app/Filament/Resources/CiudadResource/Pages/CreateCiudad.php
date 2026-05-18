<?php

namespace App\Filament\Resources\CiudadResource\Pages;

use App\Filament\Resources\CiudadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCiudad extends CreateRecord
{
    protected static bool $canCreateAnother = false;
    protected static string $resource = CiudadResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }



}
