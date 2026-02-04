<?php

namespace App\Filament\Resources\DiagnosticoResource\Pages;

use App\Filament\Resources\DiagnosticoResource;
use App\Models\RecepcionVehiculo;
use Filament\Resources\Pages\CreateRecord;

class CreateDiagnostico extends CreateRecord
{
    protected static string $resource = DiagnosticoResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_alta'] = auth()->id();
        $data['fec_alta'] = now();
        return $data;
    }

    protected function afterCreate(): void
    {
        // Cuando se crea un diagnóstico, marcar la recepción como 'Pendiente a presupuesto'
        if ($this->record->recepcion_vehiculo_id) {
            $recepcion = RecepcionVehiculo::find($this->record->recepcion_vehiculo_id);
            if ($recepcion) {
                $recepcion->estado = 'Pendiente a presupuesto';
                $recepcion->save();
            }
        }
    }

  protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }

}
