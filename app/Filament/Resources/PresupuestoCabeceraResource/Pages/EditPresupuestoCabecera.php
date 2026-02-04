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
            Actions\ViewAction::make()
                ->mutateRecordDataUsing(function (array $data): array {
                    // Cargar los detalles para el modal de ver
                    $data['presupuestoDetalles'] = $this->record->presupuestoDetalles->map(function ($detalle) {
                        return [
                            'id_detalle' => $detalle->id_detalle,
                            'cod_articulo' => $detalle->cod_articulo,
                            'cantidad' => $detalle->cantidad,
                            'precio' => $detalle->precio,
                            'total' => $detalle->total,
                            'total_iva' => $detalle->total_iva,
                        ];
                    })->toArray();
                    return $data;
                }),
            Actions\DeleteAction::make()
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los detalles existentes
        $data['presupuestoDetalles'] = $this->record->presupuestoDetalles->map(function ($detalle) {
            return [
                'id_detalle' => $detalle->id_detalle,
                'cod_articulo' => $detalle->cod_articulo,
                'cantidad' => $detalle->cantidad,
                'precio' => $detalle->precio,
                'total' => $detalle->total,
                'total_iva' => $detalle->total_iva,
            ];
        })->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalcula totales (lado servidor) antes de guardar
        $grav = 0.0; $iva = 0.0;

        if (!empty($data['presupuestoDetalles']) && is_array($data['presupuestoDetalles'])) {
            foreach ($data['presupuestoDetalles'] as &$d) {
                $cantidad  = (float)($d['cantidad'] ?? 0);
                $precio    = (float)($d['precio'] ?? 0);
                $exenta    = (float)($d['exenta'] ?? 0);
                $totalItem = $cantidad * $precio;
                $ivaItem   = max(0, ($totalItem - $exenta)) * 0.10;

                $d['total']     = $totalItem;
                $d['total_iva'] = $ivaItem;

                $grav += $totalItem;
                $iva  += $ivaItem;
            }
            unset($d);
        }

        $data['monto_gravado'] = $grav;
        $data['monto_tot_impuesto'] = $iva;
        $data['monto_general'] = $grav + $iva;

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
            if (isset($detalle['id_detalle']) && in_array($detalle['id_detalle'], $detallesExistentes)) {
                // Actualizar detalle existente
                $record->presupuestoDetalles()->where('id_detalle', $detalle['id_detalle'])->update([
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'total' => $detalle['total'],
                    'total_iva' => $detalle['total_iva'],
                ]);
                $detallesEnviados[] = $detalle['id_detalle'];
            } else {
                // Crear nuevo detalle
                $nuevoDetalle = $record->presupuestoDetalles()->create([
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'total' => $detalle['total'],
                    'total_iva' => $detalle['total_iva'],
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
        // Refuerza consistencia
        $cab = $this->record;
        $grav = 0.0; $iva = 0.0;

        foreach ($cab->presupuestoDetalles as $det) {
            $totalItem = (float)$det->cantidad * (float)$det->precio;
            $ivaItem   = max(0, ($totalItem - (float)($det->exenta ?? 0))) * 0.10;

            if ((float)$det->total !== $totalItem || (float)$det->total_iva !== $ivaItem) {
                $det->update(['total' => $totalItem, 'total_iva' => $ivaItem]);
            }
            $grav += $totalItem;
            $iva  += $ivaItem;
        }

        $cab->update([
            'monto_gravado' => $grav,
            'monto_tot_impuesto' => $iva,
            'monto_general' => $grav + $iva,
        ]);
    }
}
