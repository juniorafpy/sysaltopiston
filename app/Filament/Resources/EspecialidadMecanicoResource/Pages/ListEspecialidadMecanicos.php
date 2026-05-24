<?php

namespace App\Filament\Resources\EspecialidadMecanicoResource\Pages;

use App\Filament\Resources\EspecialidadMecanicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEspecialidadMecanicos extends ListRecords
{
    protected static string $resource = EspecialidadMecanicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Especialidad registrada exitosamente.');
                }),
        ];
    }
}
