<?php

namespace App\Filament\Resources\EntregaVehiculoResource\Pages;

use App\Filament\Resources\EntregaVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntregaVehiculos extends ListRecords
{
    protected static string $resource = EntregaVehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('nueva_entrega')
                ->label('Nueva Entrega')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url('/admin/entrega-vehiculo'),
        ];
    }
}
