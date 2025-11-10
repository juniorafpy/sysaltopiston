<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CreateReclamo extends CreateRecord
{
    protected static string $resource = ReclamoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar datos de auditoría
        $data['usuario_alta'] = Auth::id();
        $data['fecha_alta'] = now();

        // Asignar sucursal del usuario si está disponible
        if (Auth::user()->cod_sucursal) {
            $data['cod_sucursal'] = Auth::user()->cod_sucursal;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $reclamo = $this->record;

        // [ACCIÓN CLAVE] Notificar si es de prioridad Alta
        if ($reclamo->prioridad === 'Alta') {
            // Notificación para el usuario actual
            Notification::make()
                ->title('Reclamo de Alta Prioridad Registrado')
                ->body("El reclamo #{$reclamo->cod_reclamo} ha sido registrado con prioridad ALTA. Se notificará al Jefe de Servicio.")
                ->warning()
                ->persistent()
                ->send();

            // TODO: Aquí se puede agregar notificación al Jefe de Servicio
            // Por ejemplo, enviar un correo o notificación push
            // Mail::to($jefeServicio->email)->send(new ReclamoAltaPrioridadMail($reclamo));
        }

        // Notificación de éxito
        Notification::make()
            ->title('Reclamo Registrado Exitosamente')
            ->body("Reclamo #{$reclamo->cod_reclamo} registrado correctamente.")
            ->success()
            ->send();
    }

     protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }
}
