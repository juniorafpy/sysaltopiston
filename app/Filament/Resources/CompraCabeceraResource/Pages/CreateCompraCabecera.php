<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use App\Filament\Resources\CompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompraCabecera extends CreateRecord
{
    protected static ?string $title = 'Registrar Factura de Compra';
    protected static string $resource = CompraCabeceraResource::class;




protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}


}


