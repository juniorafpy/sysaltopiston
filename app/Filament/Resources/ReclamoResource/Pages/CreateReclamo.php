<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateReclamo extends CreateRecord
{
    protected static string $resource = ReclamoResource::class;
    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Guardar')
            ->submit('create');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar')
            ->color('danger')
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = \'' . $this->previousUrl . '\')');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_alta'] = auth()->user()?->id;
        $data['fecha_alta'] = now();
        
        // Eliminar campos virtuales
        unset($data['cliente_nombre']);
        unset($data['cliente_documento']);
        unset($data['vehiculo_info']);
        
        return $data;
    }
}
