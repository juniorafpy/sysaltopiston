<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPresupuestoCabecera extends ViewRecord
{
    protected static string $resource = PresupuestoCabeceraResource::class;
    protected static ?string $title = 'Ver Presupuesto';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-m-pencil-square'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['presupuestoDetalles'] = $this->record->presupuestoDetalles->map(function ($detalle) {
            $precio   = (float)$detalle->precio;
            $cantidad = (int)$detalle->cantidad;
            $total    = round($cantidad * $precio);
            $iva      = round($total / 11);
            return [
                'id_detalle'   => $detalle->id_detalle,
                'cod_articulo' => $detalle->cod_articulo,
                'cantidad'     => $cantidad,
                'precio'       => number_format($precio, 0, ',', '.'),
                'total'        => number_format($total, 0, ',', '.'),
                'total_iva'    => number_format($iva, 0, ',', '.'),
            ];
        })->toArray();

        return $data;
    }
}
