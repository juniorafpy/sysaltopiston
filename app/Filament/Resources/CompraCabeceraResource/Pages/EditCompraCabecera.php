<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use App\Filament\Resources\CompraCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompraCabecera extends EditRecord
{
    protected static string $resource = CompraCabeceraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
