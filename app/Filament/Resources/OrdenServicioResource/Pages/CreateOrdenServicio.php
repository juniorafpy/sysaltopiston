<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateOrdenServicio extends CreateRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar campos de sistema
        $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
        $data['fec_alta'] = now();
        $data['cod_sucursal'] = auth()->user()->cod_sucursal ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $ordenServicio = $this->record;

        // Reservar el stock
        $resultado = $ordenServicio->reservarStock();

        if ($resultado['success']) {
            Notification::make()
                ->success()
                ->title('Orden de servicio creada')
                ->body('El stock ha sido reservado correctamente.')
                ->send();
        } else {
            $mensajes = implode("\n", $resultado['messages']);

            Notification::make()
                ->warning()
                ->title('Orden de servicio creada con advertencia')
                ->body("No se pudo reservar todo el stock:\n\n" . $mensajes)
                ->persistent()
                ->send();
        }
    }

     protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}
}
