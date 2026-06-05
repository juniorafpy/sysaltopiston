<?php

namespace App\Filament\Resources\EspecialidadMecanicoResource\Pages;

use App\Filament\Resources\EspecialidadMecanicoResource;
use App\Models\EspecialidadMecanico;
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
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = EspecialidadMecanico::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'La especialidad ya está registrada.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Especialidad registrada exitosamente.');
                }),
        ];
    }
}
