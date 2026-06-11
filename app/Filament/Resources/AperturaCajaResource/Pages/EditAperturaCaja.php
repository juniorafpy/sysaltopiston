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

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->estado === 'Abierta' && isset($data['efectivo_real'])) {
            $totalFisico = (float)($data['efectivo_real'] ?? 0) 
                + (float)($data['tarjetas_real'] ?? 0) 
                + (float)($data['transferencias_real'] ?? 0) 
                + (float)($data['cheques_real'] ?? 0);
            
            $saldoEsperado = $this->record->saldo_esperado_calculado;
            
            $data['estado'] = 'Cerrada';
            $data['fecha_cierre'] = now()->toDateString();
            $data['hora_cierre'] = now()->toTimeString();
            $data['saldo_esperado'] = $saldoEsperado;
            $data['diferencia'] = $totalFisico - $saldoEsperado;
            $data['usuario_mod'] = Auth::id();
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
