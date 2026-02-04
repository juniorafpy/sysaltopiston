<?php

namespace App\Filament\Resources\AperturaCajaResource\Pages;

use App\Filament\Resources\AperturaCajaResource;
use App\Models\AperturaCaja;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAperturaCaja extends CreateRecord
{
    protected static string $resource = AperturaCajaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Validar que el usuario tenga un empleado asociado
        if (!$user->empleado) {
            Notification::make()
                ->danger()
                ->title('Error: Sin empleado asociado')
                ->body('Tu usuario no está asociado a un empleado. Contacta al administrador.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validar que el cajero no tenga otra caja abierta
        if (AperturaCaja::cajeroTieneCajaAbierta($user->empleado->cod_empleado)) {
            Notification::make()
                ->danger()
                ->title('Error: Ya tiene una caja abierta')
                ->body('Debe cerrar su caja actual antes de abrir una nueva.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Si cod_cajero no está en el formulario (por defecto debería estar)
        // asignarlo automáticamente desde el empleado del usuario
        if (!isset($data['cod_cajero']) || !$data['cod_cajero']) {
            $data['cod_cajero'] = $user->empleado->cod_empleado;
        }

        $data['cod_sucursal'] = $user->cod_sucursal ?? null;
        $data['estado'] = 'Abierta';
        $data['usuario_alta'] = $user->id;
        $data['fecha_alta'] = now();

        // Asegurar que fecha y hora tengan valores válidos
        $data['fecha_apertura'] = $data['fecha_apertura'] ?? now()->toDateString();
        $data['hora_apertura'] = $data['hora_apertura'] ?? now()->format('H:i:s');

        return $data;
    }    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Caja Abierta Exitosamente')
            ->body('La caja ha sido abierta y está lista para operar.')
            ->duration(5000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
