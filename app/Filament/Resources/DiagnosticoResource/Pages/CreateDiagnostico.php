<?php

namespace App\Filament\Resources\DiagnosticoResource\Pages;

use App\Filament\Resources\DiagnosticoResource;
use App\Models\RecepcionVehiculo;
use App\Traits\WithSucursalData;
use Filament\Resources\Pages\CreateRecord;

class CreateDiagnostico extends CreateRecord
{
    use WithSucursalData;

    protected static string $resource = DiagnosticoResource::class;

    protected static bool $canCreateAnother = false;

    public function mount($record = null): void
    {
        parent::mount($record);

        // Inicializar los datos de sucursal y usuario
        $this->initSucursalData();
        $this->initUsuAltaData();

        // Solo agregar los campos de sucursal y usuario sin sobrescribir el resto
        $this->data['cod_sucursal'] = $this->cod_sucursal;
        $this->data['nombre_sucursal'] = $this->nombre_sucursal;
        $this->data['nombre_usuario'] = $this->usuario_alta;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cod_sucursal'] = $data['cod_sucursal'] ?? auth()->user()?->cod_sucursal;
        $data['empleado_id'] = auth()->user()?->cod_empleado;
        $data['usuario_alta'] = auth()->user()->username ?? auth()->user()->name;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

  protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }

}
