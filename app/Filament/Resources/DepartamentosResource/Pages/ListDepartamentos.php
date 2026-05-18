<?php

namespace App\Filament\Resources\DepartamentosResource\Pages;

use App\Filament\Resources\DepartamentosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartamentos extends ListRecords
{
    protected static string $resource = DepartamentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->modalSubmitActionLabel('Guardar')
                ->successNotificationTitle(null)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['usuario_alta'] = auth()->user()->name;
                    $data['fec_alta'] = now();
                    $data['estado'] = 'A';
                    return $data;
                })
                ->after(fn () => $this->dispatch('swal:success', message: 'Departamento creado exitosamente.')),
        ];
    }
}
