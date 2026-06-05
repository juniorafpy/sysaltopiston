<?php

namespace App\Filament\Resources\ModelosResource\Pages;

use App\Filament\Resources\ModelosResource;
use App\Models\Modelos;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModelos extends ListRecords
{
    protected static string $resource = ModelosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Modelos::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                        ->where('cod_marca', $data['cod_marca'])
                        ->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'El modelo ya está registrado para esa marca.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Modelo registrado exitosamente.');
                }),
        ];
    }
}
