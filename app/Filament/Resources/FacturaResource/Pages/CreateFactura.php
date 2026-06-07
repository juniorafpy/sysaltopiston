<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use App\Models\Factura;
use App\Models\OrdenServicio;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        // Verificar si viene el parámetro 'orden_servicio_id' en la URL
        $ordenServicioId = request()->query('orden_servicio_id');

        if ($ordenServicioId) {
            $orden = OrdenServicio::with(['detalles.articulo', 'cliente.persona'])->find($ordenServicioId);

            if ($orden) {
                // 1. Resolver el Timbrado Activo explícitamente
                $timbrado = \App\Models\Timbrado::obtenerTimbradoActivo('FAC');
                $codTimbrado = $timbrado?->cod_timbrado;

                if (!$timbrado) {
                    Notification::make()
                        ->title('Sin timbrado activo')
                        ->body('No se encontró un timbrado activo y vigente para tu sucursal. Verifica que tu usuario tenga una sucursal con establecimiento configurado y que exista un timbrado disponible.')
                        ->warning()
                        ->persistent()
                        ->send();
                }

                // 2. Resolver el Cliente Correcto (cod_persona para la tabla Personas)
                // La factura usa Personas, pero la OS usa Cliente. Necesitamos el cod_persona.
                $codCliente = null;
                if ($orden->cliente) {
                    // Si el modelo Cliente tiene relación con Persona
                    if ($orden->cliente->persona) {
                        $codCliente = $orden->cliente->persona->cod_persona;
                    } 
                    // O si Cliente tiene el campo cod_persona directamente
                    elseif (isset($orden->cliente->cod_persona)) {
                        $codCliente = $orden->cliente->cod_persona;
                    }
                    // Fallback: intentar usar el mismo ID si las tablas están sincronizadas
                    else {
                        $codCliente = $orden->cod_cliente;
                    }
                }

                // 3. Preparar detalles con campos calculados
                $detalles = [];
                $subtotalGravado10 = 0;
                $totalIva10 = 0;
                $totalGeneral = 0;

                foreach ($orden->detalles as $detalle) {
                    $cantidad = $detalle->cantidad_real ?? $detalle->cantidad;
                    $precioUnitario = floatval($detalle->precio_unitario);
                    $porcentajeDescuento = 0;

                    $importeBruto = $cantidad * $precioUnitario; // Con IVA incluido
                    $montoDescuento = ($importeBruto * $porcentajeDescuento) / 100;
                    $subtotal = $importeBruto - $montoDescuento; // Con IVA
                    $montoIva = ($subtotal * 10) / 110;
                    $base = $subtotal - $montoIva; // Base imponible sin IVA
                    $porcentajeIva = 10;

                    $detalles[] = [
                        'cod_articulo' => $detalle->cod_articulo,
                        'descripcion' => $detalle->descripcion ?? $detalle->articulo?->descripcion ?? 'N/A',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'porcentaje_descuento' => $porcentajeDescuento,
                        'tipo_iva' => '10',
                        'monto_descuento' => round($montoDescuento, 2),
                        'subtotal' => round($subtotal, 2),
                        'porcentaje_iva' => $porcentajeIva,
                        'monto_iva' => round($montoIva, 2),
                        'total' => round($subtotal, 2),
                    ];

                    $subtotalGravado10 += $base;
                    $totalIva10 += $montoIva;
                }

                $totalGeneral = $subtotalGravado10 + $totalIva10;

                // Precargar datos en el formulario
                $serieFactura = null;
                if ($timbrado) {
                    $numero = $timbrado->obtenerSiguienteNumero();
                    $serieFactura = "{$timbrado->establecimiento}-{$timbrado->punto_expedicion}-{$numero}";
                }

                $fillData = [
                    'origen_factura' => 'orden_servicio',
                    'orden_servicio_id' => $orden->id,
                    'cod_cliente' => $codCliente,
                    'fecha_factura' => now()->toDateString(),
                    'cod_timbrado' => $codTimbrado,
                    'timbrado_display' => $timbrado?->numero_timbrado,
                    'serie_factura' => $serieFactura,
                    'subtotal_gravado_10' => round($subtotalGravado10, 2),
                    'total_iva_10' => round($totalIva10, 2),
                    'subtotal_gravado_5' => 0,
                    'total_iva_5' => 0,
                    'subtotal_exenta' => 0,
                    'total_general' => round($totalGeneral, 2),
                ];

                // Heredar condición de compra del presupuesto vinculado a la OS
                if ($orden->relationLoaded('presupuestoVenta') || $orden->presupuestoVenta) {
                    $fillData['cod_condicion_compra'] = $orden->presupuestoVenta->cod_condicion;
                }

                $this->form->fill($fillData);

                // Los detalles se setean directamente en el estado del formulario
                $this->data['detalles'] = $detalles;

                // Notificación de éxito
                $msg = "Se cargaron " . count($detalles) . " artículo(s) de la OS #{$orden->id}";
                Notification::make()
                    ->title('Orden de Servicio cargada')
                    ->body($msg)
                    ->success()
                    ->send();
            }
        }
    }

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
                $data['condicion_venta'] = ($condicionCompra->dias_cuotas == 0) ? 'Contado' : 'Crédito';
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

            // 3. Generar vencimientos (si es crédito)
            $factura->generarVencimientos();

            // 4. Insertar movimiento de caja (si es contado)
            $factura->insertarMovimientoCaja();

            // 5. Incrementar el número actual del timbrado
            $factura->timbrado->incrementarNumeroActual();

            // 6. Si viene de presupuesto, actualizar su estado
            if ($factura->presupuesto_venta_id) {
                $factura->presupuestoVenta->update(['estado' => 'Facturado']);
            }

            // 7. Si viene de Orden de Servicio, marcar como facturada
            if ($factura->orden_servicio_id) {
                $ordenServicio = $factura->ordenServicio;
                if ($ordenServicio) {
                    $ordenServicio->update(['facturado' => true]);
                }
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
