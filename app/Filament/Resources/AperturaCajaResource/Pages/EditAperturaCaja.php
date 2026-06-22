<?php

namespace App\Filament\Resources\AperturaCajaResource\Pages;

use App\Filament\Resources\AperturaCajaResource;
use App\Models\RecaudacionDepositar;
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $efectivo = $this->record->cobros()
            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
            ->where('cobros_formas_pago.cod_forma_cobro', 1)
            ->sum('cobros_formas_pago.monto') ?? 0;
        $tarjetas = $this->record->cobros()
            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
            ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
            ->sum('cobros_formas_pago.monto') ?? 0;
        $transferencias = $this->record->cobros()
            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
            ->where('cobros_formas_pago.cod_forma_cobro', 4)
            ->sum('cobros_formas_pago.monto') ?? 0;
        $cheques = $this->record->cobros()
            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
            ->where('cobros_formas_pago.cod_forma_cobro', 5)
            ->sum('cobros_formas_pago.monto') ?? 0;
        $total = $efectivo + $tarjetas + $transferencias + $cheques;

        $data['efectivo_sistema'] = $efectivo;
        $data['tarjetas_sistema'] = $tarjetas;
        $data['transferencias_sistema'] = $transferencias;
        $data['cheques_sistema'] = $cheques;
        $data['total_sistema'] = $total;

        return $data;
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

    protected function afterSave(): void
    {
        if ($this->record->estado === 'Cerrada') {
            // Buscar último arqueo para usar montos físicos
            $arqueo = $this->record->arqueos()->latest('fecha_alta')->first();

            if ($arqueo) {
                $efectivo = $arqueo->efectivo_fisico;
                $cheques = $arqueo->cheques_fisico;
            } else {
                // Si no hay arqueo, usar montos del sistema
                $efectivo = $this->record->cobros()
                    ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                    ->where('cobros_formas_pago.cod_forma_cobro', 1)
                    ->sum('cobros_formas_pago.monto') ?? 0;
                $cheques = $this->record->cobros()
                    ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                    ->where('cobros_formas_pago.cod_forma_cobro', 5)
                    ->sum('cobros_formas_pago.monto') ?? 0;
            }

            $monto = (float) $efectivo + (float) $cheques;

            if ($monto > 0) {
                $codRecaudacion = (RecaudacionDepositar::max('cod_recaudacion') ?? 0) + 1;

                RecaudacionDepositar::create([
                    'cod_recaudacion' => $codRecaudacion,
                    'monto' => $monto,
                    'fecha' => now()->toDateString(),
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
