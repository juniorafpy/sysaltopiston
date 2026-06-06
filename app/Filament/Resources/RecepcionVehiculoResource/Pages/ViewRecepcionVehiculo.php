<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRecepcionVehiculo extends ViewRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

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

        // Inyectar datos de auditoría para la vista
        $user = \App\Models\User::find($this->record->usuario_alta);
        $data['nombre_usuario'] = $user ? $user->name : $this->record->usuario_alta;
        $data['fec_alta'] = $this->record->fec_alta ? \Carbon\Carbon::parse($this->record->fec_alta)->format('d/m/Y H:i') : '-';

        return $data;
    }
}
