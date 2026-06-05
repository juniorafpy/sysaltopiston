<?php

namespace App\Filament\Resources\PaisResource\Pages;

use App\Filament\Resources\PaisResource;
use App\Models\Pais;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPais extends ListRecords
{
    protected static string $resource = PaisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Pais::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'Ya existe un país con esa descripción.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'País registrado exitosamente.');
                }),
        ];
    }
}
