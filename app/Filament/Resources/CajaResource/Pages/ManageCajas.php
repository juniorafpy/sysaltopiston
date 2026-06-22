<?php

namespace App\Filament\Resources\CajaResource\Pages;

use App\Filament\Resources\CajaResource;
use App\Models\Caja;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCajas extends ManageRecords
{
    protected static string $resource = CajaResource::class;

    public function getHeading(): string
    {
        return 'Lista Cajas';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
                    $data['fecha_alta'] = now();
                    return $data;
                })
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Caja::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'La caja ya está registrada.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Registrado con exito');
                }),
        ];
    }
}
