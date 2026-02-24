<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProveedorResource;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->color('warning')
                ->icon('heroicon-o-check'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Proveedor actualizado exitosamente';
    }
}
