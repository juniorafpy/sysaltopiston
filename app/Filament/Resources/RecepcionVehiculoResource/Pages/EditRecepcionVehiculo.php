<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditRecepcionVehiculo extends EditRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    protected ?int $codCombustibleItem = null;
    protected ?string $observacionesInventario = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Buscar el combustible directamente antes de llenar el formulario
        $pivotData = DB::table('recepcion_vehiculo_items_inventario')
            ->join('sm_inventario', 'recepcion_vehiculo_items_inventario.cod_inventario', '=', 'sm_inventario.cod_inventario')
            ->where('recepcion_vehiculo_items_inventario.recepcion_vehiculo_id', $this->record->id)
            ->where('sm_inventario.tipo', 'C')
            ->first();

        if ($pivotData) {
            $data['cod_combustible_item'] = $pivotData->cod_inventario;
            $data['observaciones_inventario_text'] = $pivotData->observaciones_inventario;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capturar valores antes de guardar y limpiar el array de datos del modelo
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

    protected function afterSave(): void
    {
        // Eliminar cualquier item de combustible (Tipo C) existente para evitar duplicados
        $fuelItems = $this->record->itemsInventario()->where('tipo', 'C')->pluck('sm_inventario.cod_inventario')->toArray();
        if (!empty($fuelItems)) {
            $this->record->itemsInventario()->detach($fuelItems);
        }

        // Guardar el nuevo item de combustible en la tabla pivote
        if ($this->codCombustibleItem) {
            $this->record->itemsInventario()->attach($this->codCombustibleItem, [
                'observaciones_inventario' => $this->observacionesInventario,
            ]);
        }
    }
}
