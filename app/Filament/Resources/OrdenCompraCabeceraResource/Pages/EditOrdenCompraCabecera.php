<?php

namespace App\Filament\Resources\OrdenCompraCabeceraResource\Pages;

use App\Filament\Resources\OrdenCompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenCompraCabecera extends EditRecord
{
    protected static string $resource = OrdenCompraCabeceraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
