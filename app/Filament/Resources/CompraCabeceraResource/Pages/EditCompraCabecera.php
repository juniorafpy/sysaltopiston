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

    protected function afterSave(): void
    {
        // Regenerar cuotas después de editar la factura
        $this->record->generarCuotas();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los totales calculados al abrir el formulario de edición
        if ($this->record && $this->record->detalles) {
            $subtotal = $this->record->detalles->sum('monto_total_linea');
            $iva = $subtotal * 0.10;
            $data['total_gravada'] = number_format($subtotal, 0, '', '');
            $data['tot_iva'] = number_format($iva, 0, '', '');
            $data['total_general'] = number_format($subtotal + $iva, 0, '', '');
        }
        return $data;
    }
}
