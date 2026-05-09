<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Mecanico;
use App\Models\RecepcionInventario;

class CreateRecepcionVehiculo extends CreateRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected static bool $canCreateAnother = false;

    protected ?array $inventarioData = null;

    public function mount(): void
    {
        parent::mount();

        // Cargar datos del usuario y sucursal automáticamente
        $user = auth()->user();
        
        $formData = [
            'usuario_alta' => $user->id,
            'nombre_usuario' => $user->name,
            'fecha_recepcion' => now(),
            'estado' => 'Ingresado',
        ];

        if ($user->cod_sucursal) {
            $formData['cod_sucursal'] = $user->cod_sucursal;
            
            // Cargar la sucursal si existe la relación
            if ($user->sucursal) {
                $formData['nombre_sucursal'] = $user->sucursal->descripcion;
            }
        }
        
        $this->form->fill($formData);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar usuario y sucursal
        $user = auth()->user();
        $data['cod_sucursal'] = $user->cod_sucursal ?? null;
        $data['usuario_alta'] = $user->id;
        $data['fec_alta'] = now();

        // Si se seleccionó un empleado, buscar su cod_mecanico
        if (isset($data['empleado_id'])) {
            $mecanico = Mecanico::where('cod_empleado', $data['empleado_id'])->first();
            if ($mecanico) {
                $data['cod_mecanico'] = $mecanico->cod_mecanico;
            }
        }

        // Extraer los datos del inventario antes de guardar
        if (isset($data['inventario'])) {
            $this->inventarioData = $data['inventario'];
            unset($data['inventario']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Guardar el inventario después de crear la recepción
        if (isset($this->inventarioData) && !empty($this->inventarioData)) {
            $inventarioData = $this->inventarioData;
            $inventarioData['recepcion_vehiculo_id'] = $this->record->id;

            RecepcionInventario::create($inventarioData);
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
