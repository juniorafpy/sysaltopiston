<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

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
        $user = auth()->user();
        
        // Guardar el nombre del usuario (consistente con otros módulos)
        $data['usuario_alta'] = $user?->name ?? 'Sistema';
        $data['fecha_alta'] = now();
        
        // Sucursal: si viene del formulario (OS), ok; si no, usar la del usuario
        if (empty($data['cod_sucursal']) && $user?->cod_sucursal) {
            $data['cod_sucursal'] = $user->cod_sucursal;
        }
        
        // Eliminar campos virtuales que no existen en la tabla
        unset($data['cliente_nombre']);
        unset($data['cliente_documento']);
        unset($data['vehiculo_info']);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Reclamo registrado exitosamente')
            ->body('El reclamo ha sido guardado correctamente.')
            ->success()
            ->send();
    }
}
