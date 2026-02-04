<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProveedorResource;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Guardar')
                ->submit('create')
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Proveedor creado exitosamente';
    }
}
