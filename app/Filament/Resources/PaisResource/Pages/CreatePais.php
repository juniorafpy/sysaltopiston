<?php

namespace App\Filament\Resources\PaisResource\Pages;

use App\Filament\Resources\PaisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePais extends CreateRecord
{
    protected static string $resource = PaisResource::class;

    protected function getFormActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->disableCreateAnother(), // Desactiva la opción "Crear otro"
        ];
    }
    
    
}
