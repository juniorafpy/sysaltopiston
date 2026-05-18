<?php

namespace App\Filament\Resources\CiudadResource\Pages;

use App\Filament\Resources\CiudadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCiudads extends ListRecords
{
    protected static string $resource = CiudadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->modalSubmitActionLabel('Guardar')
                ->successNotificationTitle(null)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['usuario_alta'] = auth()->user()->name;
                    $data['fec_alta'] = now();
                    $data['estado'] = 'A';
                    return $data;
                })
                ->after(fn () => $this->dispatch('swal:success', message: 'Ciudad creada exitosamente.')),
        ];
    }
}
