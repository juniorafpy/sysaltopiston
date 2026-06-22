<?php

namespace App\Filament\Resources\TipoServicioResource\Pages;

use App\Filament\Resources\TipoServicioResource;
use App\Models\TipoServicio;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoServicios extends ListRecords
{
    protected static string $resource = TipoServicioResource::class;

    public function getHeading(): string
    {
        return 'Lista Tipo de servicio';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = TipoServicio::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'El tipo de servicio ya está registrado.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Registrado con exito');
                }),
        ];
    }
}
