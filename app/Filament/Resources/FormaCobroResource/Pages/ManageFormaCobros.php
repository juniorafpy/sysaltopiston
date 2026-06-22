<?php

namespace App\Filament\Resources\FormaCobroResource\Pages;

use App\Filament\Resources\FormaCobroResource;
use App\Models\FormaCobro;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFormaCobros extends ManageRecords
{
    protected static string $resource = FormaCobroResource::class;

    public function getHeading(): string
    {
        return 'Lista Formas de Cobro';
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
                    $max = \App\Models\FormaCobro::max('cod_forma_cobro') ?? 0;
                    $data['cod_forma_cobro'] = $max + 1;
                    $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
                    $data['fec_alta'] = now();
                    return $data;
                })
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = FormaCobro::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'La forma de cobro ya está registrada.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Registrado con exito');
                }),
        ];
    }
}
