<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenServicio extends EditRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Liberar stock al eliminar
                    $record->liberarStock();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dataLimpia = [
            'estado_trabajo' => $data['estado_trabajo'] ?? $this->record->estado_trabajo,
            'usuario_mod' => auth()->user()->name ?? 'Sistema',
            'fec_mod' => now(),
        ];

        if (
            ($dataLimpia['estado_trabajo'] ?? null) === 'Finalizado' &&
            empty($this->record->fecha_finalizacion_real)
        ) {
            $dataLimpia['fecha_finalizacion_real'] = now();
        }

        return $dataLimpia;
    }
}
