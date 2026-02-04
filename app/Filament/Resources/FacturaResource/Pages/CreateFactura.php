<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use App\Models\Factura;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Mutate form data before creating the record
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remover campo virtual 'origen_factura'
        $origenFactura = $data['origen_factura'] ?? 'directa';
        unset($data['origen_factura']);

        // Asegurar que solo el campo correcto tenga valor según el origen
        if ($origenFactura === 'presupuesto') {
            $data['orden_servicio_id'] = null;
        } elseif ($origenFactura === 'orden_servicio') {
            $data['presupuesto_venta_id'] = null;
        } else {
            // Directa
            $data['presupuesto_venta_id'] = null;
            $data['orden_servicio_id'] = null;
        }

        // Determinar condicion_venta desde cod_condicion_compra
        if (isset($data['cod_condicion_compra'])) {
            $condicionCompra = \App\Models\CondicionCompra::find($data['cod_condicion_compra']);
            if ($condicionCompra) {
                $data['condicion_venta'] = ($condicionCompra->cant_cuota == 0) ? 'Contado' : 'Crédito';
            }
        }

        // Asegurar que el estado sea 'Emitida'
        $data['estado'] = 'Emitida';

        // Obtener y validar el timbrado
        $timbrado = \App\Models\Timbrado::findOrFail($data['cod_timbrado']);

        if (!$timbrado->estaVigente()) {
            throw new \Exception('El timbrado no está vigente.');
        }

        // Obtener el siguiente número de factura
        $numeroFactura = $timbrado->obtenerSiguienteNumero();
        $data['numero_factura'] = $timbrado->formatearNumeroFactura($numeroFactura);

        return $data;
    }

    /**
     * Hook después de crear el registro (cuando ya existen los detalles)
     */
    protected function afterCreate(): void
    {
        $factura = $this->record;

        try {
            // 1. Recalcular totales desde los detalles ya guardados por Filament
            $factura->load('detalles');

            $totalesCalculados = [
                'subtotal_gravado_10' => 0,
                'subtotal_gravado_5' => 0,
                'subtotal_exenta' => 0,
                'total_iva_10' => 0,
                'total_iva_5' => 0,
                'total_general' => 0
            ];

            foreach ($factura->detalles as $detalle) {
                $tipoIva = $detalle->tipo_iva;
                $subtotal = floatval($detalle->subtotal); // Incluye IVA
                $montoIva = floatval($detalle->monto_iva);

                if ($tipoIva === '10') {
                    // Subtotal gravado es SIN IVA
                    $totalesCalculados['subtotal_gravado_10'] += ($subtotal - $montoIva);
                    $totalesCalculados['total_iva_10'] += $montoIva;
                } elseif ($tipoIva === '5') {
                    // Subtotal gravado es SIN IVA
                    $totalesCalculados['subtotal_gravado_5'] += ($subtotal - $montoIva);
                    $totalesCalculados['total_iva_5'] += $montoIva;
                } elseif ($tipoIva === 'Exenta') {
                    $totalesCalculados['subtotal_exenta'] += $subtotal;
                }

                // Total general es la suma de todos los subtotales (con IVA incluido)
                $totalesCalculados['total_general'] += $subtotal;
            }

            // Ya no necesitamos calcular total_general aquí porque lo hicimos en el loop

            // Actualizar totales
            $factura->update($totalesCalculados);

            // 2. Insertar en libro IVA
            $factura->insertarLibroIva();

            // 3. Insertar en cuenta corriente (si es crédito)
            $factura->insertarCCSaldo();

            // 4. Insertar movimiento de caja (si es contado)
            $factura->insertarMovimientoCaja();

            // 5. Incrementar el número actual del timbrado
            $factura->timbrado->incrementarNumeroActual();

            // 6. Si viene de presupuesto, actualizar su estado
            if ($factura->presupuesto_venta_id) {
                $factura->presupuestoVenta->update(['estado' => 'Facturado']);
            }

            // Recargar para tener datos actualizados
            $factura->refresh();

            // Notificaciones de éxito
            Notification::make()
                ->title('Factura generada exitosamente')
                ->success()
                ->body("Factura Nro: {$factura->numero_factura} - Total: Gs. " . number_format($factura->total_general, 0, ',', '.'))
                ->send();

            if ($factura->condicion_venta === 'Crédito') {
                Notification::make()
                    ->title('Saldo registrado en Cuenta Corriente')
                    ->info()
                    ->body("Se registró el saldo de Gs. " . number_format($factura->total_general, 0, ',', '.') . " en la cuenta del cliente.")
                    ->send();
            } elseif ($factura->condicion_venta === 'Contado') {
                Notification::make()
                    ->title('Ingreso registrado en Caja')
                    ->info()
                    ->body("Se registró el ingreso de Gs. " . number_format($factura->total_general, 0, ',', '.') . " en la caja abierta.")
                    ->send();
            }

            Notification::make()
                ->title('Registro en Libro IVA')
                ->info()
                ->body("La factura se registró en el Libro IVA correctamente.")
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al procesar la factura')
                ->danger()
                ->body($e->getMessage())
                ->persistent()
                ->send();

            Log::error("Error en afterCreate de Factura: " . $e->getMessage());
        }
    }

    /**
     * Get the redirect URL after creating the record
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
