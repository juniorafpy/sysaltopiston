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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar nombre del mecánico para mostrar en el Placeholder
        if (!empty($data['cod_mecanico'])) {
            $mecanico = \App\Models\Mecanico::with('empleado.persona')->find($data['cod_mecanico']);
            if ($mecanico?->empleado?->persona) {
                $nombre = trim(($mecanico->empleado->persona->nombres ?? '') . ' ' . ($mecanico->empleado->persona->apellidos ?? ''));
                $data['mecanico_nombre_valor'] = $nombre;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dataLimpia = [
            'estado_trabajo' => $data['estado_trabajo'] ?? $this->record->estado_trabajo,
            'cod_mecanico' => $data['cod_mecanico'] ?? $this->record->cod_mecanico,
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
