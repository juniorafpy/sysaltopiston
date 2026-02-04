<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPresupuestoCabeceras extends ListRecords
{
    protected static string $resource = PresupuestoCabeceraResource::class;
     protected static ?string $title = 'Listado de Presupuestos';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Crear Presupuesto')->createAnother(false),

        ];
    }
}
