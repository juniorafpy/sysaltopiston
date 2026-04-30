<?php

namespace App\Filament\Resources\OrdenCompraCabeceraResource\Pages;

use App\Filament\Resources\OrdenCompraCabeceraResource;
use App\Models\OrdenCompraDetalle;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrdenCompraCabecera extends CreateRecord
{
    protected static string $resource = OrdenCompraCabeceraResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCancelFormAction()->color('danger'),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $parseNum = fn ($v) => (float) str_replace('.', '', str_replace(',', '.', (string)($v ?? 0)));

        $detalles = $data['ordenCompraDetalles'] ?? [];
        unset($data['ordenCompraDetalles']);

        $record = static::getModel()::create($data);

        foreach ($detalles as $d) {
            $precio   = $parseNum($d['precio']);
            $cantidad = $parseNum($d['cantidad']);
            $total    = round($cantidad * $precio);
            $iva      = round($total / 11);

            OrdenCompraDetalle::create([
                'nro_orden_compra' => $record->nro_orden_compra,
                'cod_articulo'     => $d['cod_articulo'],
                'cantidad'         => $cantidad,
                'precio'           => $precio,
                'total'            => $total,
                'total_iva'        => $iva,
            ]);
        }

        return $record;
    }
}

