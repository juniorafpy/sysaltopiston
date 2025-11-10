<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresupuestoCabecera extends EditRecord
{
    protected static string $resource = PresupuestoCabeceraResource::class;
    protected static ?string $title = 'Editar Presupuesto';

     protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
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

        $data['total_gravada'] = $grav;
        $data['tot_iva']       = $iva;
        $data['total']         = $grav + $iva;

        return $data;
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
            'total_gravada' => $grav,
            'tot_iva'       => $iva,
            'total'         => $grav + $iva,
        ]);
    }
}
