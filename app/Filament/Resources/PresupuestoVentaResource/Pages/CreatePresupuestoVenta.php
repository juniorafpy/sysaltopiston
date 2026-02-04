<?php

namespace App\Filament\Resources\PresupuestoVentaResource\Pages;

use App\Filament\Resources\PresupuestoVentaResource;
use App\Models\Diagnostico;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePresupuestoVenta extends CreateRecord
{
    protected static string $resource = PresupuestoVentaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $diagnosticoId = request()->integer('diagnostico_id');

        if ($diagnosticoId) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente')->find($diagnosticoId);

            if ($diagnostico) {
                $data['diagnostico_id'] = $diagnostico->id;
                $data['recepcion_vehiculo_id'] = $diagnostico->recepcion_vehiculo_id;
                $data['cliente_id'] = $diagnostico->recepcionVehiculo?->cliente?->cod_persona;
                $data['observaciones_diagnostico'] = $diagnostico->diagnostico_mecanico ?? '';
            }
        }

        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $diagnosticoId = request()->integer('diagnostico_id');

        if ($diagnosticoId) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente')->find($diagnosticoId);

            if ($diagnostico) {
                $this->form->fill([
                    'diagnostico_id' => $diagnostico->id,
                    'recepcion_vehiculo_id' => $diagnostico->recepcion_vehiculo_id,
                    'cliente_id' => $diagnostico->recepcionVehiculo?->cliente?->cod_persona,
                    'observaciones_diagnostico' => $diagnostico->diagnostico_mecanico ?? '',
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$subtotal, $iva, $total] = PresupuestoVentaResource::summarizeDetalles($data['detalles'] ?? []);

        $data['total'] = $total;

        return $data;
    }

     protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }
}
