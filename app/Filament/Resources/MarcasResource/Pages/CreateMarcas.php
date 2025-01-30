<?php

namespace App\Filament\Resources\MarcasResource\Pages;

use App\Filament\Resources\MarcasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMarcas extends CreateRecord
{
    protected static string $resource = MarcasResource::class;

    protected  static bool $canCreateAnother =  false;

 /*   protected function getFormActions(): array
{
    return [
        Actions\CreateAction::make()->CreateAnother(false),
    ];
}*/
}
