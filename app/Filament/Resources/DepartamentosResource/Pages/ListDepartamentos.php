<?php

namespace App\Filament\Resources\DepartamentosResource\Pages;

use App\Filament\Resources\DepartamentosResource;
use App\Models\Departamentos;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartamentos extends ListRecords
{
    protected static string $resource = DepartamentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Departamentos::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                        ->where('cod_pais', $data['cod_pais'])
                        ->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'El departamento ya está registrado en ese país.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Departamento registrado exitosamente.');
                }),
        ];
    }
}
