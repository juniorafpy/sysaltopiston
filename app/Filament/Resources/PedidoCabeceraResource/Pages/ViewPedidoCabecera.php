<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Filament\Resources\PedidoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPedidoCabecera extends ViewRecord
{
    protected static string $resource = PedidoCabeceraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => in_array($record->estado, ['PENDIENTE'])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar datos relacionados para la visualización
        $record = $this->getRecord();

        if ($record->ped_empleados && $record->ped_empleados->persona) {
            $data['nombre_empleado'] = $record->ped_empleados->persona->nombre_completo;
        }

        if ($record->sucursal_ped) {
            $data['nombre_sucursal'] = $record->sucursal_ped->descripcion;
        }

        $data['fec_alta_display'] = $record->fec_alta
            ? \Carbon\Carbon::parse($record->fec_alta)->format('d/m/Y H:i')
            : '-';

        return $data;
    }
}
