<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresupuestoCabecera extends EditRecord
{
    protected static string $resource = PresupuestoCabeceraResource::class;
    protected static ?string $title = 'Editar Presupuesto';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('manual')
                ->label('Manual de Usuario')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(fn () => route('pdf.manual-usuario.presupuesto-compra'))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los detalles existentes
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $grav = 0.0; $iva = 0.0;

        if (!empty($data['presupuestoDetalles']) && is_array($data['presupuestoDetalles'])) {
            foreach ($data['presupuestoDetalles'] as &$d) {
                $cantidad  = (int) str_replace('.', '', (string)($d['cantidad'] ?? 0));
                $precio    = (float) str_replace('.', '', (string)($d['precio'] ?? 0));
                $totalItem = round($cantidad * $precio);
                $ivaItem   = round($totalItem / 11);
                $netItem   = $totalItem - $ivaItem;

                $d['total']     = $totalItem;
                $d['total_iva'] = $ivaItem;

                $grav += $netItem;
                $iva  += $ivaItem;
            }
            unset($d);
        }

        $data['monto_gravado']      = round($grav);
        $data['monto_tot_impuesto'] = round($iva);
        $data['monto_general']      = round($grav + $iva);

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Extraer los detalles antes de actualizar la cabecera
        $detalles = $data['presupuestoDetalles'] ?? [];
        unset($data['presupuestoDetalles']);

        // Actualizar la cabecera
        $record->update($data);

        // Obtener IDs de detalles existentes
        $detallesExistentes = $record->presupuestoDetalles->pluck('id_detalle')->toArray();
        $detallesEnviados = [];

        // Actualizar o crear detalles
        foreach ($detalles as $detalle) {
                $parseNum = fn($v) => (float) str_replace('.', '', (string)($v ?? 0));
                $cantidad  = (int)$parseNum($detalle['cantidad']);
                $precio    = $parseNum($detalle['precio']);
                $total     = round($cantidad * $precio);
                $totalIva  = round($total / 11);

            if (isset($detalle['id_detalle']) && in_array($detalle['id_detalle'], $detallesExistentes)) {
                $record->presupuestoDetalles()->where('id_detalle', $detalle['id_detalle'])->update([
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad'     => $cantidad,
                    'precio'       => $precio,
                    'total'        => $total,
                    'total_iva'    => $totalIva,
                ]);
                $detallesEnviados[] = $detalle['id_detalle'];
            } else {
                $nuevoDetalle = $record->presupuestoDetalles()->create([
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad'     => $cantidad,
                    'precio'       => $precio,
                    'total'        => $total,
                    'total_iva'    => $totalIva,
                ]);
                $detallesEnviados[] = $nuevoDetalle->id_detalle;
            }
        }

        // Eliminar detalles que fueron removidos
        $detallesAEliminar = array_diff($detallesExistentes, $detallesEnviados);
        if (!empty($detallesAEliminar)) {
            $record->presupuestoDetalles()->whereIn('id_detalle', $detallesAEliminar)->delete();
        }

        return $record;
    }

    protected function afterSave(): void
    {
        $cab = $this->record;
        $grav = 0.0; $iva = 0.0;

        foreach ($cab->presupuestoDetalles as $det) {
            $totalItem = round((float)$det->cantidad * (float)$det->precio);
            $ivaItem   = round($totalItem / 11);
            $netItem   = $totalItem - $ivaItem;

            $det->update(['total' => $totalItem, 'total_iva' => $ivaItem]);

            $grav += $netItem;
            $iva  += $ivaItem;
        }

        $cab->update([
            'monto_gravado'      => round($grav),
            'monto_tot_impuesto' => round($iva),
            'monto_general'      => round($grav + $iva),
        ]);
    }
}
