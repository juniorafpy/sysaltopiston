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

        if (AperturaCaja::where('usuario', $user->name)
            ->where('estado', 'Abierta')
            ->exists()) {
            Notification::make()
                ->danger()
                ->title('Error: Ya tiene una caja abierta')
                ->body('Debe cerrar su caja actual antes de abrir una nueva.')
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['usuario'] = $user->name;
        $data['cod_sucursal'] = $user->cod_sucursal ?? null;
        $data['estado'] = 'Abierta';
        $data['fecha_alta'] = now();

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
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
