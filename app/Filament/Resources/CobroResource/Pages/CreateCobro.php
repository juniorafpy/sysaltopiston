<?php

namespace App\Filament\Resources\CobroResource\Pages;

use App\Filament\Resources\CobroResource;
use App\Models\Cobro;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateCobro extends CreateRecord
{
    protected static string $resource = CobroResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar que los totales coincidan
        $detalles = $data['detalles'] ?? [];
        $formasPago = $data['formas_pago'] ?? [];

        $totalDetalles = collect($detalles)->sum('monto_cuota');
        $totalFormasPago = collect($formasPago)->sum('monto');

        if ($totalDetalles != $totalFormasPago) {
            Notification::make()
                ->title('Error de validación')
                ->danger()
                ->body("El total a cobrar (Gs. " . number_format($totalDetalles, 0, ',', '.') .
                       ") no coincide con el total de formas de pago (Gs. " . number_format($totalFormasPago, 0, ',', '.') . ")")
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validar que no se superen los saldos pendientes
        foreach ($detalles as $detalle) {
            $factura = \App\Models\Factura::find($detalle['cod_factura']);
            $saldoPendiente = $factura->getSaldoPendiente();

            if ($detalle['monto_cuota'] > $saldoPendiente) {
                Notification::make()
                    ->title('Error de validación')
                    ->danger()
                    ->body("El monto de la factura {$factura->numero_factura} (Gs. " .
                           number_format($detalle['monto_cuota'], 0, ',', '.') .
                           ") supera el saldo pendiente (Gs. " .
                           number_format($saldoPendiente, 0, ',', '.') . ")")
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        // Obtener la apertura de caja actual
        $usuario = Auth::user();
        if (!$usuario->empleado) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Tu usuario no está asociado a un empleado')
                ->persistent()
                ->send();

            $this->halt();
        }

        $aperturaCaja = \App\Models\AperturaCaja::where('cod_cajero', $usuario->empleado->cod_empleado)
            ->where('fecha_cierre', null)
            ->orderBy('cod_apertura', 'desc')
            ->first();

        if (!$aperturaCaja) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('No tienes una caja abierta')
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['cod_apertura'] = $aperturaCaja->cod_apertura;
        $data['monto_total'] = $totalDetalles;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Usar el método crearCobroCompleto del modelo
        return Cobro::crearCobroCompleto($data);
    }

    protected function afterCreate(): void
    {
        $cobro = $this->record;
        $detalles = $cobro->detalles;
        $formasPago = $cobro->formasPago;

        // Contar facturas únicas
        $facturasUnicas = $detalles->pluck('cod_factura')->unique()->count();
        $totalCuotas = $detalles->count();

        // Notificación principal
        Notification::make()
            ->title('Cobro registrado exitosamente')
            ->success()
            ->body("Se registró el cobro N° {$cobro->cod_cobro} por Gs. " .
                   number_format($cobro->monto_total, 0, ',', '.') .
                   " | {$facturasUnicas} factura(s) | {$totalCuotas} cuota(s) | " .
                   "{$formasPago->count()} forma(s) de pago")
            ->send();

        // Verificar si alguna factura quedó cancelada
        foreach ($detalles->pluck('cod_factura')->unique() as $codFactura) {
            $factura = \App\Models\Factura::find($codFactura);
            $saldoPendiente = $factura->getSaldoPendiente();

            if ($saldoPendiente <= 0) {
                Notification::make()
                    ->title('Factura cancelada')
                    ->success()
                    ->body("La factura {$factura->numero_factura} ha sido cancelada completamente.")
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
