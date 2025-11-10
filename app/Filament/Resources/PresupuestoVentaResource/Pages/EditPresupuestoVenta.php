<?php

namespace App\Filament\Resources\PresupuestoVentaResource\Pages;

use App\Filament\Resources\PresupuestoVentaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresupuestoVenta extends EditRecord
{
    protected static string $resource = PresupuestoVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        [$subtotal, $iva, $total] = PresupuestoVentaResource::summarizeDetalles($data['detalles'] ?? []);

        $data['subtotal_general'] = $subtotal;
        $data['impuestos_totales'] = $iva;
        $data['total'] = $total;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$subtotal, $iva, $total] = PresupuestoVentaResource::summarizeDetalles($data['detalles'] ?? []);

        $data['total'] = $total;

        return $data;
    }
}
