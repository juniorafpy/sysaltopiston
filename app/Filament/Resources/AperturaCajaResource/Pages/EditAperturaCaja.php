<?php

namespace App\Filament\Resources\AperturaCajaResource\Pages;

use App\Filament\Resources\AperturaCajaResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAperturaCaja extends EditRecord
{
    protected static string $resource = AperturaCajaResource::class;

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->label('Cerrar apertura');
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->estado === 'Abierta') {
            $data['estado'] = 'Cerrada';
            $data['fecha_cierre'] = now()->toDateString();
            $data['hora_cierre'] = now()->toTimeString();
            $data['saldo_esperado'] = $this->record->saldo_esperado_calculado;
            $data['usuario_mod'] = Auth::user()->name;
            $data['fecha_mod'] = now();
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->record->estado === 'Cerrada') {
            return Notification::make()
                ->success()
                ->title('Caja Cerrada Exitosamente')
                ->body('La caja ha sido cerrada y los totales han sido registrados.')
                ->duration(5000);
        }

        return Notification::make()
            ->success()
            ->title('Actualizado')
            ->body('Los datos han sido actualizados correctamente.')
            ->duration(3000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
