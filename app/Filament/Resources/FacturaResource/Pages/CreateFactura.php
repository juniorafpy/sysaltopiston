<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use App\Models\Factura;
use App\Models\OrdenServicio;
use App\Models\PresupuestoVenta;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Guardar');
    }

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
                    $porcentajeDescuento = floatval($detalle->porcentaje_descuento ?? 0);

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

                $user = Auth::user();
                $sucursal = $user && $user->cod_sucursal ? \App\Models\Sucursal::find($user->cod_sucursal) : null;

                $referencia = 'OS #' . $orden->id;

                $fillData = [
                    'orden_servicio_id' => $orden->id,
                    'referencia' => $referencia,
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
                    'sucursal_display' => $sucursal?->descripcion ?? 'Sin sucursal',
                    'usuario_alta' => $user?->name ?? 'Sistema',
                    'fecha_alta' => now()->format('d/m/Y H:i:s'),
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

        // Verificar si viene el parámetro 'presupuesto_venta_id' en la URL
        $presupuestoVentaId = request()->query('presupuesto_venta_id');

        if ($presupuestoVentaId) {
            $presupuesto = PresupuestoVenta::with(['detalles.articulo', 'cliente'])->find($presupuestoVentaId);

            if ($presupuesto) {
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

                // El cod_cliente del presupuesto es cod_cliente (tabla clientes), pero la factura usa cod_persona
                $codCliente = $presupuesto->cod_cliente;
                if ($presupuesto->cliente?->persona) {
                    $codCliente = $presupuesto->cliente->persona->cod_persona;
                }

                $detalles = [];
                $subtotalGravado10 = 0;
                $totalIva10 = 0;
                $totalGeneral = 0;

                foreach ($presupuesto->detalles as $detalle) {
                    $cantidad = $detalle->cantidad;
                    $precioUnitario = floatval($detalle->precio_unitario);
                    $porcentajeDescuento = floatval($detalle->porcentaje_descuento ?? 0);

                    $importeBruto = $cantidad * $precioUnitario;
                    $montoDescuento = ($importeBruto * $porcentajeDescuento) / 100;
                    $subtotal = $importeBruto - $montoDescuento;
                    $montoIva = ($subtotal * 10) / 110;
                    $base = $subtotal - $montoIva;
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

                $serieFactura = null;
                if ($timbrado) {
                    $numero = $timbrado->obtenerSiguienteNumero();
                    $serieFactura = "{$timbrado->establecimiento}-{$timbrado->punto_expedicion}-{$numero}";
                }

                $user = Auth::user();
                $sucursal = $user && $user->cod_sucursal ? \App\Models\Sucursal::find($user->cod_sucursal) : null;

                $referencia = 'Presupuesto #' . $presupuesto->id;

                $fillData = [
                    'presupuesto_venta_id' => $presupuesto->id,
                    'referencia' => $referencia,
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
                    'sucursal_display' => $sucursal?->descripcion ?? 'Sin sucursal',
                    'usuario_alta' => $user?->name ?? 'Sistema',
                    'fecha_alta' => now()->format('d/m/Y H:i:s'),
                ];

                if ($presupuesto->cod_condicion) {
                    $fillData['cod_condicion_compra'] = $presupuesto->cod_condicion;
                }

                $this->form->fill($fillData);
                $this->data['detalles'] = $detalles;

                $msg = "Se cargaron " . count($detalles) . " artículo(s) del Presupuesto #{$presupuesto->id}";
                Notification::make()
                    ->title('Presupuesto de Venta cargado')
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
        try {
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

            // Auditoría
            $user = Auth::user();
            $data['cod_sucursal'] = $user->cod_sucursal ?? null;
            $data['usuario_alta'] = $user->name;
            $data['fecha_alta'] = now();

            return $data;
        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Hook before creating - validate stock for Mostrador presupuestos
     */
    protected function beforeCreate(): void
    {
        try {
            $data = $this->form->getState();
            
            // VALIDAR STOCK para presupuestos tipo Mostrador (cod_tipo_venta = 1)
            if (!empty($data['presupuesto_venta_id'])) {
                $presupuesto = \App\Models\PresupuestoVenta::with('detalles.articulo')->find($data['presupuesto_venta_id']);
                if ($presupuesto && $presupuesto->cod_tipo_venta == 1) {
                    $sucursal = $data['cod_sucursal'] ?? auth()->user()->cod_sucursal;
                    $errores = [];
                    
                    foreach ($presupuesto->detalles as $detalle) {
                        $codArticulo = $detalle->cod_articulo;
                        $cantidad = $detalle->cantidad;
                        $stock = \App\Models\ExisteStock::where('cod_articulo', $codArticulo)
                            ->where('cod_sucursal', $sucursal)
                            ->first();
                        $stockDisponible = $stock?->stock_disponible ?? 0;
                        
                        if ($stockDisponible < $cantidad) {
                            $descripcion = $detalle->descripcion ?? $detalle->articulo?->descripcion ?? "Artículo #{$codArticulo}";
                            $errores[] = "{$descripcion}: Disponible {$stockDisponible}, Requerido {$cantidad}";
                        }
                    }
                    
                    if (!empty($errores)) {
                        $mensaje = "No hay suficiente stock para facturar:<br>" . implode("<br>", $errores);
                        Notification::make()
                            ->title('Stock Insuficiente')
                            ->body($mensaje)
                            ->danger()
                            ->persistent()
                            ->send();

                        $this->halt();
                    }
                }
            }
        } catch (\Filament\Support\Exceptions\Halt $e) {
            // Dejar que el Halt se propague para detener el formulario sin mostrar error genérico
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'message' => 'Error: ' . $e->getMessage()
            ]);
            $this->halt();
        }
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

            // 6. Si viene de presupuesto, actualizar su estado y marcar como facturado
            if ($factura->presupuesto_venta_id) {
                $factura->presupuestoVenta->update(['estado' => 'Facturado', 'ind_facturada' => 'S']);
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

            // Emitir SweetAlert de éxito (modal persistente)
            $this->dispatch('swal:success-modal', [
                'title' => 'Factura registrada',
                'message' => "Factura Nro: {$factura->numero_factura} registrada correctamente"
            ]);

        } catch (\Exception $e) {
            $this->dispatch('swal:error', [
                'message' => "Error al procesar la factura: " . $e->getMessage()
            ]);

            Log::error("Error en afterCreate de Factura: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the redirect URL after creating the record
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Suprimir notificación nativa de Filament
     */
    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
