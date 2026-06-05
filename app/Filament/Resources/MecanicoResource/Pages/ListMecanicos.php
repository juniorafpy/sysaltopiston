<?php

namespace App\Filament\Resources\MecanicoResource\Pages;

use App\Filament\Resources\MecanicoResource;
use App\Models\Mecanico;
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
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Mecanico::where('cod_empleado', $data['cod_empleado'])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'Este empleado ya está registrado como mecánico.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Mecánico registrado exitosamente.');
                }),
        ];
    }
}
