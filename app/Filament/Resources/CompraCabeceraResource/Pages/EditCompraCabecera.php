<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\CompraCabeceraResource;

class EditCompraCabecera extends EditRecord
{
    protected static ?string $title = 'Editar Factura de Compra';
    protected static string $resource = CompraCabeceraResource::class;
    
    // Propiedad para almacenar detalles temporalmente
    protected array $detalles = [];

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction eliminado - no se permite eliminar facturas desde edición
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->action('save')
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

    protected function getSavedNotificationTitle(): ?string
    {
        return '✅ Factura de compra actualizada exitosamente';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar detalles desde la relación
        if ($this->record && $this->record->detalles) {
            $data['detalles'] = $this->record->detalles->map(function ($detalle) {
                return [
                    'cod_articulo' => $detalle->cod_articulo,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'porcentaje_iva' => $detalle->porcentaje_iva ?? 10,
                    'total_iva' => number_format((float)$detalle->total_iva, 2, '.', ''),
                    'monto_total_linea' => number_format((float)$detalle->monto_total_linea, 2, '.', ''),
                ];
            })->toArray();
            
            // Cargar los totales calculados
            $subtotal = $this->record->detalles->sum('monto_total_linea');
            $iva = $subtotal * 0.10;
            $data['total_gravada'] = number_format($subtotal, 0, '', '');
            $data['tot_iva'] = number_format($iva, 0, '', '');
            $data['total_general'] = number_format($subtotal + $iva, 0, '', '');
        }
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Guardar detalles temporalmente
        $this->detalles = $data['detalles'] ?? [];
        
        // Remover detalles del array principal
        unset($data['detalles']);
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Eliminar detalles existentes
        $this->record->detalles()->delete();
        
        // Crear nuevos detalles
        if (!empty($this->detalles)) {
            foreach ($this->detalles as $detalle) {
                $this->record->detalles()->create([
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
        
        // Regenerar cuotas después de editar la factura
        $this->record->generarCuotas();
    }
}
