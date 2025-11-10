<?php

namespace App\Filament\Resources\OrdenCompraCabeceraResource\Pages;

use App\Filament\Resources\OrdenCompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenCompraCabecera extends CreateRecord
{
    protected static string $resource = OrdenCompraCabeceraResource::class;


    protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}
}
