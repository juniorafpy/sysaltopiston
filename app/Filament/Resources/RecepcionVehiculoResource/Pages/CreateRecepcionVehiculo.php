<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Mecanico;

class CreateRecepcionVehiculo extends CreateRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected static bool $canCreateAnother = false;

    protected ?int $codCombustibleItem = null;
    protected ?string $observacionesInventario = null;

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
        if (isset($data['cod_combustible_item'])) {
            $this->codCombustibleItem = $data['cod_combustible_item'];
            unset($data['cod_combustible_item']);
        }

        if (isset($data['observaciones_inventario_text'])) {
            $this->observacionesInventario = $data['observaciones_inventario_text'];
            unset($data['observaciones_inventario_text']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Guardar el item de combustible en la tabla pivote
        if ($this->codCombustibleItem) {
            $this->record->itemsInventario()->attach($this->codCombustibleItem, [
                'observaciones_inventario' => $this->observacionesInventario,
            ]);
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
