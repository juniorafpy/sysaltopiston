<?php

namespace App\Filament\Resources\CiudadResource\Pages;

use App\Filament\Resources\CiudadResource;
use App\Models\Ciudad;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCiudads extends ListRecords
{
    protected static string $resource = CiudadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Ciudad::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                        ->where('cod_departamento', $data['cod_departamento'])
                        ->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'La ciudad ya está registrada en ese departamento.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Ciudad registrada exitosamente.');
                }),
        ];
    }
}
