<?php

namespace App\Filament\Resources\MecanicoResource\Pages;

use App\Filament\Resources\MecanicoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMecanico extends CreateRecord
{
    protected static string $resource = MecanicoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Guardar');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
