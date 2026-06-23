<?php

namespace App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;

use App\Filament\Resources\NotaCreditoDebitoCompraResource;
use App\Models\NotaCreditoDebitoCompra;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaCreditoDebitoCompra extends CreateRecord
{
    protected static string $resource = NotaCreditoDebitoCompraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $codProveedor = $data['cod_proveedor'] ?? null;
        $tipo = $data['tip_comprobante'] ?? null;
        $serie = $data['ser_comprobante'] ?? null;
        $numero = $data['nro_comprobante'] ?? null;
        $idCompra = $data['id_compra_cabecera'] ?? null;

        // Validar número de nota duplicado
        if ($codProveedor && $tipo && $serie && $numero) {
            $existe = NotaCreditoDebitoCompra::where('cod_proveedor', $codProveedor)
                ->where('tip_comprobante', $tipo)
                ->where('ser_comprobante', $serie)
                ->where('nro_comprobante', $numero)
                ->exists();

            if ($existe) {
                Notification::make()
                    ->danger()
                    ->title('Número de nota duplicado')
                    ->body("Ya existe una nota {$tipo} {$serie}-{$numero} para este proveedor.")
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }

        // Validar que la factura no tenga ya una nota del mismo tipo
        if ($idCompra && $tipo) {
            $existeFactura = NotaCreditoDebitoCompra::where('id_compra_cabecera', $idCompra)
                ->where('tip_comprobante', $tipo)
                ->exists();

            if ($existeFactura) {
                Notification::make()
                    ->danger()
                    ->title('Factura ya referenciada')
                    ->body("La factura de compra seleccionada ya tiene una nota de {$tipo} registrada.")
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->load(['detalles', 'compraCabecera', 'motivo']);
        $this->record->procesarEfectos();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
