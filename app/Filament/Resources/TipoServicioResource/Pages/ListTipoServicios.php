<?php

namespace App\Filament\Resources\TipoServicioResource\Pages;

use App\Filament\Resources\TipoServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoServicios extends ListRecords
{
    protected static string $resource = TipoServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Tipo de servicio registrado exitosamente.');
                }),
        ];
    }
}
