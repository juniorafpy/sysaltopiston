<?php

namespace App\Filament\Resources\OrdenCompraCabeceraResource\Pages;

use App\Filament\Resources\OrdenCompraCabeceraResource;
use App\Models\OrdenCompraDetalle;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrdenCompraCabecera extends EditRecord
{
    protected static string $resource = OrdenCompraCabeceraResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $detalles = OrdenCompraDetalle::where('nro_orden_compra', $data['nro_orden_compra'])->get();

        $data['ordenCompraDetalles'] = $detalles->map(function ($d) {
            return [
                'cod_articulo' => $d->cod_articulo,
                'cantidad'     => (int)$d->cantidad,
                'precio'       => number_format((float)$d->precio, 0, ',', '.'),
                'total'        => number_format((float)$d->total, 0, ',', '.'),
                'total_iva'    => number_format((float)$d->total_iva, 0, ',', '.'),
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $parseNum = fn ($v) => (float) str_replace('.', '', str_replace(',', '.', (string)($v ?? 0)));

        $detalles = $data['ordenCompraDetalles'] ?? [];
        unset($data['ordenCompraDetalles']);

        $record->update($data);

        OrdenCompraDetalle::where('nro_orden_compra', $record->nro_orden_compra)->delete();

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

