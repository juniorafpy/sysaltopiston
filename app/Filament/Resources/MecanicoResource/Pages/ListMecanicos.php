<?php

namespace App\Filament\Resources\MecanicoResource\Pages;

use App\Filament\Resources\MecanicoResource;
use App\Filament\Exports\MecanicoExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMecanicos extends ListRecords
{
    protected static string $resource = MecanicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Mecánico registrado exitosamente.');
                }),
        ];
    }
}
