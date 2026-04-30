<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\CompraCabeceraResource;
use App\Models\OrdenCompraCabecera;

class CreateCompraCabecera extends CreateRecord
{
    protected static ?string $title = 'Registrar Factura de Compra';
    protected static string $resource = CompraCabeceraResource::class;
    
    // Propiedad para almacenar detalles temporalmente
    protected array $detalles = [];

    public function mount(): void
    {
        parent::mount();
        
        // Verificar si viene el parámetro 'orden_compra' en la URL
        $ordenCompraId = request()->query('orden_compra');

        if ($ordenCompraId) {
            // Cargar la orden de compra con sus detalles
            $ordenCompra = OrdenCompraCabecera::with('ordenCompraDetalles.articulo')
                ->find($ordenCompraId);

            if ($ordenCompra) {
                // Preparar detalles
                $detalles = [];
                foreach ($ordenCompra->ordenCompraDetalles as $detalle) {
                    $cantidad = (float) $detalle->cantidad;
                    $precio = (float) $detalle->precio;
                    $totalConIva = $cantidad * $precio; // El precio incluye IVA
                    $iva = $totalConIva / 11; // Extraer IVA del total (10%)

                    $detalles[] = [
                        'cod_articulo' => $detalle->cod_articulo,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'porcentaje_iva' => 10,
                        'total_iva' => number_format($iva, 2, '.', ''),
                        'monto_total_linea' => number_format($totalConIva, 2, '.', ''),
                    ];
                }

                // Log para debug
                \Log::info('Precargando factura desde OC', [
                    'orden_compra_id' => $ordenCompraId,
                    'proveedor' => $ordenCompra->cod_proveedor,
                    'condicion' => $ordenCompra->cod_condicion_compra,
                    'cantidad_detalles' => count($detalles),
                    'detalles' => $detalles
                ]);

                // DEBUG TEMPORAL - DESCOMENTAR PARA VER LOS DATOS
                // dd([
                //     'orden' => $ordenCompra->toArray(),
                //     'detalles' => $detalles,
                //     'proveedor_nombre' => $ordenCompra->proveedor->personas_pro->nombre_completo ?? 'N/A'
                // ]);

                // Precargar datos de cabecera y detalles
                $this->form->fill([
                    'nro_oc_ref' => $ordenCompra->nro_orden_compra,
                    'cod_proveedor' => $ordenCompra->cod_proveedor,
                    'cod_condicion_compra' => $ordenCompra->cod_condicion_compra,
                    'cod_sucursal' => $ordenCompra->cod_sucursal,
                    'observacion' => 'Basado en OC Nro. ' . $ordenCompra->nro_orden_compra,
                    'tip_comprobante' => 'FAC',
                    'fec_comprobante' => now()->toDateString(),
                    'fec_vencimiento' => now()->addDays(30)->toDateString(),
                    'usuario_alta' => auth()->user()->name ?? 'Sistema',
                    'fecha_alta' => \Carbon\Carbon::now()->toDateTimeString(),
                    'detalles' => $detalles,
                ]);

                // Notificación de éxito
                \Filament\Notifications\Notification::make()
                    ->title('Datos cargados exitosamente')
                    ->body("Se han cargado los datos de la Orden de Compra Nro. {$ordenCompra->nro_orden_compra}")
                    ->success()
                    ->duration(5000)
                    ->send();
            } else {
                // Notificación de error
                \Filament\Notifications\Notification::make()
                    ->title('Orden de compra no encontrada')
                    ->body("No se pudo encontrar la orden de compra con ID: {$ordenCompraId}")
                    ->danger()
                    ->duration(5000)
                    ->send();
            }
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Guardar')
                ->action('create')
                ->keyBindings(['mod+s'])
                ->color('warning')
                ->icon('heroicon-o-check'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Factura de compra registrada exitosamente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guardar los detalles temporalmente para procesarlos después
        $this->detalles = $data['detalles'] ?? [];
        
        // Remover detalles del array principal para evitar errores
        unset($data['detalles']);
        
        // Asegurar que tip_comprobante esté presente
        if (!isset($data['tip_comprobante'])) {
            $data['tip_comprobante'] = 'FAC';
        }
        
        // Establecer usuario y fecha de alta (el modelo boot() también lo hace, pero por seguridad)
        $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
        $data['fecha_alta'] = \Carbon\Carbon::now();
        
        // Calcular totales antes de guardar
        $totalGravado = 0;
        $totalImpuesto = 0;
        $totalGeneral = 0;
        
        foreach ($this->detalles as $detalle) {
            $montoTotal = is_numeric($detalle['monto_total_linea']) 
                ? (float) $detalle['monto_total_linea']
                : (float) str_replace(['.', ','], ['', '.'], $detalle['monto_total_linea']);
            
            $iva = is_numeric($detalle['total_iva'])
                ? (float) $detalle['total_iva']
                : (float) str_replace(['.', ','], ['', '.'], $detalle['total_iva']);
            
            $totalGeneral += $montoTotal;
            $totalImpuesto += $iva;
            $totalGravado += ($montoTotal - $iva);
        }
        
        $data['monto_general'] = $totalGeneral;
        $data['monto_tot_impuesto'] = $totalImpuesto;
        $data['monto_gravado'] = $totalGravado;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Guardar los detalles de la compra
        if (!empty($this->detalles)) {
            foreach ($this->detalles as $detalle) {
                $this->record->detalles()->create([
                    'tip_comprobante' => $this->record->tip_comprobante,
                    'ser_comprobante' => $this->record->ser_comprobante,
                    'nro_comprobante' => $this->record->nro_comprobante,
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => is_numeric($detalle['precio_unitario']) 
                        ? $detalle['precio_unitario'] 
                        : (float) str_replace(['.', ','], ['', '.'], $detalle['precio_unitario']),
                    'porcentaje_iva' => $detalle['porcentaje_iva'] ?? 10,
                    'total_iva' => is_numeric($detalle['total_iva'])
                        ? $detalle['total_iva']
                        : (float) str_replace(['.', ','], ['', '.'], $detalle['total_iva']),
                    'monto_total_linea' => is_numeric($detalle['monto_total_linea'])
                        ? $detalle['monto_total_linea']
                        : (float) str_replace(['.', ','], ['', '.'], $detalle['monto_total_linea']),
                ]);
            }
        }
        
        // Generar cuotas automáticamente después de crear la factura
        $this->record->generarCuotas();
        
        // Registrar en el libro IVA de compras
        $this->record->registrarLibroIva();
    }
}

