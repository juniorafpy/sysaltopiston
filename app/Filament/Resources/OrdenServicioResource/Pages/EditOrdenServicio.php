<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenServicio extends EditRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Liberar stock al eliminar
                    $record->liberarStock();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['usuario_mod'] = auth()->user()->name ?? 'Sistema';
        $data['fec_mod'] = now();

        // Filtrar detalles vacíos (sin cod_articulo)
        if (isset($data['detalles']) && is_array($data['detalles'])) {
            $data['detalles'] = array_filter($data['detalles'], function ($detalle) {
                return !empty($detalle['cod_articulo']);
            });

            // Reindexar el array
            $data['detalles'] = array_values($data['detalles']);
        }

        return $data;
    }
}
