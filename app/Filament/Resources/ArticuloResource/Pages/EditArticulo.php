<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticulo extends EditRecord
{
    protected static string $resource = ArticuloResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Guardar'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        $this->dispatch('swal:success', message: 'Artículo actualizado exitosamente.');
    }
}
