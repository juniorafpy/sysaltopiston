<?php

namespace App\Filament\Resources\EspecialidadMecanicoResource\Pages;

use App\Filament\Resources\EspecialidadMecanicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEspecialidadMecanico extends EditRecord
{
    protected static string $resource = EspecialidadMecanicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
