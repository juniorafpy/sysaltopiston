<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReclamo extends EditRecord
{
    protected static string $resource = ReclamoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Guardar')
            ->submit('save');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar')
            ->color('danger')
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = \'' . $this->previousUrl . '\')');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Eliminar campos virtuales
        unset($data['cliente_nombre']);
        unset($data['cliente_documento']);
        unset($data['vehiculo_info']);
        
        return $data;
    }

    protected function afterFill(): void
    {
        // Cargar datos del cliente y vehículo al editar
        $reclamo = $this->record;
        $os = $reclamo->ordenServicio;
        
        if ($os) {
            $cliente = $os->cliente;
            $persona = $cliente?->persona;
            
            $this->form->fill([
                ...$this->form->getState(),
                'cliente_nombre' => $persona?->nombre_completo ?? 'N/A',
                'cliente_documento' => $persona?->nro_documento ?? 'N/A',
                'vehiculo_info' => $reclamo->vehiculo 
                    ? "{$reclamo->vehiculo->marca?->descripcion} {$reclamo->vehiculo->modelo?->descripcion} - {$reclamo->vehiculo->matricula}"
                    : 'Sin vehículo',
            ]);
        }
    }
}
