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
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->estado === 'Abierta' && $this->record->movimientos()->count() === 0)
                ->requiresConfirmation()
                ->modalHeading('Eliminar Apertura de Caja')
                ->modalDescription('¿Está seguro de eliminar esta apertura? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se está cerrando la caja
        if ($this->record->estado === 'Abierta' && isset($data['efectivo_real'])) {
            // Calcular valores automáticamente
            $saldoEsperado = $this->record->saldo_esperado_calculado;
            $diferencia = $data['efectivo_real'] - $saldoEsperado;

            $data['estado'] = 'Cerrada';
            $data['fecha_cierre'] = now()->toDateString();
            $data['hora_cierre'] = now()->toTimeString();
            $data['saldo_esperado'] = $saldoEsperado;
            $data['diferencia'] = $diferencia;
            $data['monto_depositar'] = max(0, $data['efectivo_real'] - $this->record->monto_inicial);
            $data['usuario_mod'] = Auth::id();
            $data['fecha_mod'] = now();

            // Si hay diferencia, mostrar advertencia
            if ($diferencia != 0) {
                $tipo = $diferencia > 0 ? 'Sobrante' : 'Faltante';
                $monto = number_format(abs($diferencia), 0, ',', '.');

                Notification::make()
                    ->warning()
                    ->title("Diferencia Detectada: {$tipo}")
                    ->body("Se detectó una diferencia de {$monto} Gs. Verifique las observaciones.")
                    ->persistent()
                    ->send();
            }
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->record->estado === 'Cerrada') {
            $diferencia = $this->record->diferencia;

            if ($diferencia == 0) {
                return Notification::make()
                    ->success()
                    ->title('Caja Cerrada Exitosamente')
                    ->body('La caja ha sido cerrada correctamente. Cuadre perfecto.')
                    ->duration(5000);
            } else {
                $tipo = $diferencia > 0 ? 'Sobrante' : 'Faltante';
                return Notification::make()
                    ->warning()
                    ->title('Caja Cerrada con Diferencia')
                    ->body("La caja ha sido cerrada. {$tipo}: " . number_format(abs($diferencia), 0, ',', '.') . ' Gs.')
                    ->duration(8000);
            }
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
